<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\HR\ManageFinalSettlementAction;
use App\Actions\HR\PostFinalSettlementAction;
use App\Actions\HR\ReverseFinalSettlementAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\ReverseTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\ClearanceArea;
use App\Enums\ClearanceSourceKind;
use App\Enums\EmployeeClearanceItemStatus;
use App\Enums\EmployeeClearanceStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\FinalSettlementComponentType;
use App\Enums\FinalSettlementStatus;
use App\Enums\JournalStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Filament\Pages\FinalSettlementReports;
use App\Filament\Resources\FinalSettlements\Pages\ListFinalSettlements;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeClearance;
use App\Models\EmployeeClearanceItem;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use App\Models\FinalSettlement;
use App\Models\FinalSettlementAccountMapping;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Reports\FinalSettlementReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinalSettlementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_completed_clearance_source_evidence_and_three_independent_actors_are_required(): void
    {
        [$company, $employment, $separation, $clearance, $maker, $reviewer, $approver] = $this->context();
        $workflow = app(ManageFinalSettlementAction::class);
        $clearance->update(['status' => EmployeeClearanceStatus::InProgress]);
        $this->expectValidationException(fn () => $workflow->prepare($separation, $maker));
        $clearance->update(['status' => EmployeeClearanceStatus::Completed]);
        $settlement = $workflow->prepare($separation, $maker);

        $this->expectValidationException(fn () => $workflow->addApprovedComponent(
            $settlement,
            FinalSettlementComponentType::Salary,
            '1.0000',
            '100000.0000',
            'Salary through last working date',
            '',
            [],
            $maker,
        ));
        $workflow->addApprovedComponent(
            $settlement,
            FinalSettlementComponentType::Salary,
            '1.0000',
            '100000.0000',
            'Salary through last working date',
            'HR-SALARY-CALC-001',
            ['approved_by' => 'HR', 'cutoff' => '2026-07-31'],
            $maker,
        );
        EmployeeClearanceItem::factory()->create([
            'company_id' => $company,
            'employee_clearance_id' => $clearance,
            'source_kind' => ClearanceSourceKind::Configured,
            'source_key' => 'asset:late-recovery',
            'area' => ClearanceArea::Assets,
            'status' => EmployeeClearanceItemStatus::RecoveryRecommended,
            'recovery_recommendation_amount' => '500.0000',
            'recovery_recommendation_notes' => 'Approved after settlement draft.',
        ]);
        $this->expectValidationException(fn () => $workflow->submit($settlement, $maker));
        $workflow->prepare($separation, $maker);
        $workflow->submit($settlement->refresh(), $maker);
        $this->expectValidationException(fn () => $workflow->review($settlement->refresh(), $maker));
        $workflow->review($settlement->refresh(), $reviewer);
        $this->expectValidationException(fn () => $workflow->approve($settlement->refresh(), $reviewer));
        $workflow->approve($settlement->refresh(), $approver);

        $settlement->refresh();
        $this->assertSame(FinalSettlementStatus::Approved, $settlement->status);
        $this->assertSame('100000.0000', $settlement->earning_total);
        $this->assertSame('500.0000', $settlement->recovery_total);
        $this->assertSame('99500.0000', $settlement->net_amount);
        $this->assertSame($employment->getKey(), $settlement->employment_id);
        $this->assertNotNull($settlement->source_checksum);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_posting_treasury_settlement_report_and_reversal_reconcile(): void
    {
        [$company, $employment, $separation, , $maker, $reviewer, $approver, $poster] = $this->context();
        $workflow = app(ManageFinalSettlementAction::class);
        $settlement = $workflow->prepare($separation, $maker);
        $workflow->addApprovedComponent(
            $settlement,
            FinalSettlementComponentType::Salary,
            '1.0000',
            '100000.0000',
            'Salary through last working date',
            'HR-SALARY-CALC-002',
            ['approved' => true],
            $maker,
        );
        $workflow->addApprovedComponent(
            $settlement,
            FinalSettlementComponentType::AssetRecovery,
            '1.0000',
            '15000.0000',
            'Approved damaged asset recovery',
            'CLR-ASSET-001',
            ['approved' => true],
            $maker,
        );
        $workflow->submit($settlement, $maker);
        $workflow->review($settlement->refresh(), $reviewer);
        $workflow->approve($settlement->refresh(), $approver);
        app(PostFinalSettlementAction::class)->handle($settlement->refresh(), $poster);
        app(PostFinalSettlementAction::class)->handle($settlement->refresh(), $poster);

        $settlement->refresh();
        $this->assertSame(FinalSettlementStatus::Posted, $settlement->status);
        $this->assertSame('85000.0000', $settlement->net_amount);
        $this->assertSame('100000.0000', $settlement->journalEntry->debit_total);
        $this->assertSame(1, $company->journalEntries()->where('source_type', FinalSettlement::class)->count());

        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $oversized = TreasuryTransaction::factory()->paymentFrom(
            $company,
            $bank->accountingMapping()->firstOrFail()->account,
            $bank,
        )->create([
            'purpose' => TreasuryPurpose::Settlement,
            'counterparty_type' => TreasuryCounterpartyType::Employment,
            'employment_id' => $employment,
            'party_id' => null,
            'amount' => '85001.0000',
            'transaction_date' => '2026-07-31',
            'prepared_by_id' => $maker,
        ]);
        $oversized->allocations()->create([
            'company_id' => $company->getKey(),
            'allocatable_type' => FinalSettlement::class,
            'allocatable_id' => $settlement->getKey(),
            'allocation_type' => TreasuryAllocationType::FinalSettlement,
            'amount' => '85001.0000',
        ]);
        $this->expectValidationException(fn () => app(SubmitTreasuryTransactionAction::class)
            ->handle($oversized, $maker));
        $oversized->allocations()->delete();

        $payment = TreasuryTransaction::factory()->paymentFrom(
            $company,
            $bank->accountingMapping()->firstOrFail()->account,
            $bank,
        )->create([
            'purpose' => TreasuryPurpose::Settlement,
            'counterparty_type' => TreasuryCounterpartyType::Employment,
            'employment_id' => $employment,
            'party_id' => null,
            'amount' => '85000.0000',
            'transaction_date' => '2026-07-31',
            'prepared_by_id' => $maker,
        ]);
        $payment->allocations()->create([
            'company_id' => $company->getKey(),
            'allocatable_type' => FinalSettlement::class,
            'allocatable_id' => $settlement->getKey(),
            'allocation_type' => TreasuryAllocationType::FinalSettlement,
            'amount' => '85000.0000',
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($payment, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($payment, $reviewer);
        $this->expectValidationException(fn () => app(ReverseFinalSettlementAction::class)->handle(
            $settlement->refresh(),
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Cannot reverse with an approved payment.',
        ));
        app(PostTreasuryTransactionAction::class)->handle($payment, $poster);

        $this->assertSame(FinalSettlementStatus::Settled, $settlement->refresh()->status);
        $this->assertSame('0.0000', $settlement->postedOpenAmount());
        $report = app(FinalSettlementReport::class)->forCompany($company)->sole();
        $this->assertTrue($report['operational_gl_reconciled']);
        $this->assertTrue($report['treasury_reconciled']);
        $this->assertSame('85000.0000', $report['treasury_settled']);
        $this->assertSame($settlement->reference_number, app(FinalSettlementReport::class)
            ->settlementLetter($settlement)['reference_number']);

        app(ReverseTreasuryTransactionAction::class)->handle(
            $payment->refresh(),
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Payment correction.',
        );
        $this->assertSame(FinalSettlementStatus::Posted, $settlement->refresh()->status);
        app(ReverseFinalSettlementAction::class)->handle(
            $settlement->refresh(),
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Settlement correction.',
        );
        $this->assertSame(FinalSettlementStatus::Reversed, $settlement->refresh()->status);
        $this->assertSame(JournalStatus::Reversed, $settlement->journalEntry->refresh()->status);
        $this->assertNotNull($settlement->reversal_journal_entry_id);
    }

    public function test_tenant_ui_is_company_scoped(): void
    {
        [$company, $employment, $separation, , $maker] = $this->context();
        $workflow = app(ManageFinalSettlementAction::class);
        $settlement = $workflow->prepare($separation, $maker);
        $workflow->addApprovedComponent(
            $settlement,
            FinalSettlementComponentType::Salary,
            '1.0000',
            '1000.0000',
            'Approved salary',
            'SAL-OVER-001',
            ['approved' => true],
            $maker,
        );

        $otherSettlement = FinalSettlement::factory()->create();
        $maker->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($maker);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
        Livewire::test(ListFinalSettlements::class)
            ->assertCanSeeTableRecords([$settlement])
            ->assertCanNotSeeTableRecords([$otherSettlement]);
        Livewire::test(FinalSettlementReports::class)
            ->assertSee($settlement->reference_number)
            ->assertDontSee($otherSettlement->reference_number)
            ->assertSuccessful();

        $this->assertSame($employment->getKey(), $settlement->employment_id);
    }

    public function test_zero_balance_settlement_closes_and_reverses_without_empty_accounting_entries(): void
    {
        [, , $separation, , $maker, $reviewer, $approver, $poster] = $this->context();
        $workflow = app(ManageFinalSettlementAction::class);
        $settlement = $workflow->prepare($separation, $maker);
        foreach ([
            [FinalSettlementComponentType::Salary, 'Zero balance salary'],
            [FinalSettlementComponentType::AssetRecovery, 'Equal approved recovery'],
        ] as [$type, $description]) {
            $workflow->addApprovedComponent(
                $settlement,
                $type,
                '1.0000',
                '1000.0000',
                $description,
                "ZERO-{$type->value}",
                ['approved' => true],
                $maker,
            );
        }
        $workflow->submit($settlement->refresh(), $maker);
        $workflow->review($settlement->refresh(), $reviewer);
        $workflow->approve($settlement->refresh(), $approver);

        app(PostFinalSettlementAction::class)->handle($settlement->refresh(), $poster);
        $this->assertSame(FinalSettlementStatus::Settled, $settlement->refresh()->status);
        $this->assertSame('0.0000', $settlement->net_amount);
        $this->assertNull($settlement->journal_entry_id);
        $this->assertDatabaseCount('journal_entries', 0);

        app(ReverseFinalSettlementAction::class)->handle(
            $settlement,
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Zero-balance correction.',
        );
        $this->assertSame(FinalSettlementStatus::Reversed, $settlement->refresh()->status);
        $this->assertNull($settlement->reversal_journal_entry_id);
    }

    /** @return array{Company, Employment, EmploymentSeparation, EmployeeClearance, User, User, User, User} */
    private function context(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $role = Role::findOrCreate('super_admin');
        [$maker, $reviewer, $approver, $poster] = User::factory()
            ->count(4)->create()->each->assignRole($role)->all();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'ending_date' => '2026-07-31',
            'employment_status' => 'resigned',
        ]);
        $separation = EmploymentSeparation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment,
            'status' => EmploymentSeparationStatus::Approved,
            'approved_last_working_date' => '2026-07-31',
            'approved_by_id' => $approver,
            'approved_at' => now(),
        ]);
        $clearance = EmployeeClearance::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment,
            'employment_separation_id' => $separation,
            'status' => EmployeeClearanceStatus::Completed,
            'prepared_by_id' => $maker,
            'completed_by_id' => $reviewer,
            'completed_at' => now(),
        ]);
        $salary = $company->accounts()->where('code', '5100')->firstOrFail();
        $otherIncome = $company->accounts()->where('code', '4700')->firstOrFail();
        FinalSettlementAccountMapping::query()->create([
            'company_id' => $company->getKey(),
            'component_type' => FinalSettlementComponentType::Salary,
            'account_id' => $salary->getKey(),
            'is_active' => true,
        ]);
        FinalSettlementAccountMapping::query()->create([
            'company_id' => $company->getKey(),
            'component_type' => FinalSettlementComponentType::AssetRecovery,
            'account_id' => $otherIncome->getKey(),
            'is_active' => true,
        ]);

        return [$company, $employment, $separation, $clearance, $maker, $reviewer, $approver, $poster];
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
