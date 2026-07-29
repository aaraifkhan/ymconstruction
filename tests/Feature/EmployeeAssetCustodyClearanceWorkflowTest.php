<?php

namespace Tests\Feature;

use App\Actions\HR\ManageEmployeeClearanceAction;
use App\Actions\HR\TransitionEmployeeAssetCustodyAction;
use App\Enums\AssetCustodyExceptionType;
use App\Enums\ClearanceSourceKind;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Enums\EmployeeClearanceItemStatus;
use App\Enums\EmployeeClearanceStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentSeparationStatus;
use App\Filament\Resources\ClearanceChecklistTemplates\Pages\ListClearanceChecklistTemplates;
use App\Filament\Resources\EmployeeAssetCustodies\Pages\ListEmployeeAssetCustodies;
use App\Filament\Resources\EmployeeClearances\Pages\ListEmployeeClearances;
use App\Models\AssetTransfer;
use App\Models\ClearanceChecklistTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssetCustody;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\LeaveLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeAssetCustodyClearanceWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_asset_issue_acknowledgement_transfer_and_return_preserve_single_custodian_and_immutable_history(): void
    {
        [$company, $maker, $issuer, $receiver, $returnReceiver] = $this->context();
        $employee = Employee::factory()->create(['user_id' => $receiver]);
        $employment = Employment::factory()->forCompany($company)->create(['employee_id' => $employee]);
        $nextEmployment = Employment::factory()->forCompany($company)->create();
        $asset = FixedAsset::factory()->create(['company_id' => $company, 'prepared_by_id' => $maker]);
        $custody = EmployeeAssetCustody::factory()->create([
            'company_id' => $company,
            'fixed_asset_id' => $asset,
            'employment_id' => $employment,
            'prepared_by_id' => $maker,
        ]);
        $duplicate = EmployeeAssetCustody::factory()->create([
            'company_id' => $company,
            'fixed_asset_id' => $asset,
            'employment_id' => $nextEmployment,
            'prepared_by_id' => $maker,
        ]);
        $workflow = app(TransitionEmployeeAssetCustodyAction::class);

        $custody->update(['issued_condition' => 'Excellent']);
        $workflow->issue($custody, $issuer);
        $this->expectValidationException(fn () => $custody->refresh()->update(['issued_condition' => 'Changed']));
        $this->expectValidationException(fn () => $workflow->issue($duplicate, $issuer));
        $workflow->acknowledge($custody->refresh(), $receiver);
        $transferred = $workflow->transfer(
            $custody->refresh(),
            $nextEmployment,
            CarbonImmutable::parse('2026-07-29'),
            'Good',
            'Team reassignment',
            $issuer,
        );

        $this->assertSame(EmployeeAssetCustodyStatus::Transferred, $custody->refresh()->status);
        $this->assertSame(EmployeeAssetCustodyStatus::Issued, $transferred->status);
        $this->assertSame($nextEmployment->getKey(), $asset->refresh()->custodian_employment_id);
        $this->assertSame(1, EmployeeAssetCustody::query()
            ->where('fixed_asset_id', $asset->getKey())
            ->whereIn('status', [
                EmployeeAssetCustodyStatus::Issued->value,
                EmployeeAssetCustodyStatus::Acknowledged->value,
                EmployeeAssetCustodyStatus::ReturnPending->value,
                EmployeeAssetCustodyStatus::Exception->value,
            ])->count());
        $transfer = AssetTransfer::query()->sole();
        try {
            $transfer->update(['reason' => 'Changed']);
            $this->fail('Expected immutable transfer evidence.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        $workflow->requestReturn($transferred, 'Employment ending', $issuer);
        $workflow->acceptReturn(
            $transferred->refresh(),
            CarbonImmutable::parse('2026-07-30'),
            'Good',
            'All accessories received',
            $returnReceiver,
        );
        $this->assertSame(EmployeeAssetCustodyStatus::Returned, $transferred->refresh()->status);
        $this->assertNull($asset->refresh()->custodian_employment_id);
        $this->assertSame(6, $custody->events()->count() + $transferred->events()->count());
        try {
            $custody->events()->firstOrFail()->update(['reason' => 'Changed']);
            $this->fail('Expected immutable custody event.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_damage_recommendation_and_clearance_recovery_recommendations_do_not_post_finance(): void
    {
        [$company, $maker, $issuer, $submitter, $completer] = $this->context();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $asset = FixedAsset::factory()->create(['company_id' => $company, 'prepared_by_id' => $maker]);
        $custody = EmployeeAssetCustody::factory()->create([
            'company_id' => $company,
            'fixed_asset_id' => $asset,
            'employment_id' => $employment,
            'prepared_by_id' => $maker,
        ]);
        $assetWorkflow = app(TransitionEmployeeAssetCustodyAction::class);
        $assetWorkflow->issue($custody, $issuer);
        $assetWorkflow->reportException(
            $custody->refresh(),
            AssetCustodyExceptionType::Damage,
            'Screen damaged',
            '15000.0000',
            'Recommend HR-10 recovery review',
            $issuer,
        );
        $this->assertSame(0, JournalEntry::query()->count());
        $this->assertSame(0, TreasuryTransaction::query()->count());

        EmployeeFinancing::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'type' => EmployeeFinancingType::Loan,
            'status' => EmployeeFinancingStatus::Active,
            'reference_number' => 'LOAN-001',
            'requested_by_id' => $maker,
        ]);
        EmployeeFinancing::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'type' => EmployeeFinancingType::Advance,
            'status' => EmployeeFinancingStatus::Active,
            'reference_number' => 'ADV-001',
            'requested_by_id' => $maker,
        ]);
        LeaveLedgerEntry::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'recorded_by_id' => $maker,
            'units' => '3.00',
        ]);
        ClearanceChecklistTemplate::factory()->create([
            'company_id' => $company,
            'code' => 'IT-ACCESS',
            'name' => 'Disable system access',
            'area' => 'it',
        ]);
        $separation = EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'status' => EmploymentSeparationStatus::Approved,
            'approved_last_working_date' => '2026-07-31',
        ]);
        $clearanceWorkflow = app(ManageEmployeeClearanceAction::class);
        $clearance = $clearanceWorkflow->prepare($separation, $maker);

        $this->assertEqualsCanonicalizing(
            [
                ClearanceSourceKind::Asset->value,
                ClearanceSourceKind::Loan->value,
                ClearanceSourceKind::Advance->value,
                ClearanceSourceKind::Leave->value,
                ClearanceSourceKind::Handover->value,
                ClearanceSourceKind::Configured->value,
            ],
            $clearance->items()->get()->pluck('source_kind')
                ->map(fn (ClearanceSourceKind $kind): string => $kind->value)
                ->unique()->values()->all(),
        );
        $clearanceWorkflow->submit($clearance, $submitter);
        foreach ($clearance->items()->get() as $item) {
            $decision = $item->source_kind === ClearanceSourceKind::Asset
                ? EmployeeClearanceItemStatus::RecoveryRecommended
                : EmployeeClearanceItemStatus::Cleared;
            $clearanceWorkflow->decideItem(
                $item,
                $decision,
                'Department reviewed.',
                $issuer,
                $decision === EmployeeClearanceItemStatus::RecoveryRecommended ? '15000.0000' : null,
                $decision === EmployeeClearanceItemStatus::RecoveryRecommended ? 'For HR-10 approval only.' : null,
            );
        }
        $clearanceWorkflow->complete($clearance->refresh(), $completer);

        $this->assertSame(EmployeeClearanceStatus::Completed, $clearance->refresh()->status);
        $this->assertSame(0, JournalEntry::query()->count());
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_company_boundaries_and_tenant_lists_are_enforced(): void
    {
        [$company, $maker] = $this->context();
        $otherCompany = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $otherAsset = FixedAsset::factory()->create(['company_id' => $otherCompany, 'prepared_by_id' => $maker]);
        $this->expectValidationException(fn () => EmployeeAssetCustody::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'fixed_asset_id' => $otherAsset,
            'prepared_by_id' => $maker,
        ]));

        $asset = FixedAsset::factory()->create(['company_id' => $company, 'prepared_by_id' => $maker]);
        $custody = EmployeeAssetCustody::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'fixed_asset_id' => $asset,
            'prepared_by_id' => $maker,
        ]);
        $separation = EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'status' => EmploymentSeparationStatus::Approved,
            'approved_last_working_date' => '2026-07-31',
        ]);
        $clearance = app(ManageEmployeeClearanceAction::class)->prepare($separation, $maker);
        $template = ClearanceChecklistTemplate::factory()->create(['company_id' => $company]);

        $this->actingAs($maker);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
        Livewire::test(ListEmployeeAssetCustodies::class)
            ->assertCanSeeTableRecords([$custody])
            ->assertSuccessful();
        Livewire::test(ListEmployeeClearances::class)
            ->assertCanSeeTableRecords([$clearance])
            ->assertSuccessful();
        Livewire::test(ListClearanceChecklistTemplates::class)
            ->assertCanSeeTableRecords([$template])
            ->assertSuccessful();
    }

    public function test_departmental_clearance_permission_only_allows_its_own_area(): void
    {
        [$company, $maker, $submitter] = $this->context();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        ClearanceChecklistTemplate::factory()->create([
            'company_id' => $company,
            'code' => 'IT-ACCESS',
            'name' => 'Disable system access',
            'area' => 'it',
        ]);
        $separation = EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'status' => EmploymentSeparationStatus::Approved,
            'approved_last_working_date' => '2026-07-31',
        ]);
        $workflow = app(ManageEmployeeClearanceAction::class);
        $clearance = $workflow->prepare($separation, $maker);
        $workflow->submit($clearance, $submitter);

        $itReviewer = User::factory()->create();
        $itReviewer->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $itReviewer->givePermissionTo(Permission::findOrCreate('ClearIt:EmployeeClearance'));
        $itItem = $clearance->items()->where('area', 'it')->firstOrFail();
        $hrItem = $clearance->items()->where('area', 'hr')->firstOrFail();
        $workflow->decideItem($itItem, EmployeeClearanceItemStatus::Cleared, 'Access disabled.', $itReviewer);

        $this->expectException(AuthorizationException::class);
        $workflow->decideItem($hrItem, EmployeeClearanceItemStatus::Cleared, 'Leave reviewed.', $itReviewer);
    }

    /** @return array{Company, User, User, User, User} */
    private function context(): array
    {
        $company = Company::factory()->create();
        $role = Role::findOrCreate('super_admin');
        [$first, $second, $third, $fourth] = User::factory()
            ->count(4)->create()->each->assignRole($role)->all();

        return [$company, $first, $second, $third, $fourth];
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
