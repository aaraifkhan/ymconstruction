<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Assets\ApproveAssetDisposalAction;
use App\Actions\Assets\ApproveDepreciationRunAction;
use App\Actions\Assets\ApproveFixedAssetAction;
use App\Actions\Assets\CapitalizeFixedAssetAction;
use App\Actions\Assets\GenerateDepreciationRunAction;
use App\Actions\Assets\PostAssetDisposalAction;
use App\Actions\Assets\PostDepreciationRunAction;
use App\Actions\Assets\ReverseAssetDisposalAction;
use App\Actions\Assets\SubmitDepreciationRunAction;
use App\Actions\Assets\SubmitFixedAssetAction;
use App\Actions\Assets\TransferFixedAssetAction;
use App\Enums\AccountingProfile;
use App\Enums\AssetAccountingStatus;
use App\Enums\AssetStatus;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\Company;
use App\Models\DepreciationRun;
use App\Models\FixedAsset;
use App\Models\User;
use App\Reports\FixedAssetReconciliationReport;
use App\Reports\FixedAssetRegisterReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FixedAssetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_capitalization_depreciation_transfer_disposal_and_reversal_reconcile(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $category = AssetCategory::query()->create([
            'company_id' => $company->getKey(), 'code' => 'COMP', 'name' => 'Computers',
            'cost_account_id' => $company->accounts()->where('code', '1240')->value('id'),
            'accumulated_depreciation_account_id' => $company->accounts()->where('code', '1290')->value('id'),
            'depreciation_expense_account_id' => $company->accounts()->where('code', '6100')->value('id'),
            'disposal_gain_account_id' => $company->accounts()->where('code', '4700')->value('id'),
            'disposal_loss_account_id' => $company->accounts()->where('code', '6900')->value('id'),
            'default_useful_life_months' => 12,
        ]);
        $asset = FixedAsset::query()->create([
            'company_id' => $company->getKey(), 'asset_category_id' => $category->getKey(),
            'capitalization_credit_account_id' => $company->accounts()->where('code', '1111')->value('id'),
            'asset_number' => 'FA-0001', 'name' => 'Development Laptop', 'acquired_on' => '2026-07-01',
            'available_for_use_on' => '2026-07-01', 'acquisition_cost' => '120000.0000',
            'residual_value' => '12000.0000', 'useful_life_months' => 12, 'prepared_by_id' => $maker->getKey(),
        ]);
        app(SubmitFixedAssetAction::class)->handle($asset, $maker);
        app(ApproveFixedAssetAction::class)->handle($asset, $approver);
        app(CapitalizeFixedAssetAction::class)->handle($asset, $poster);
        app(CapitalizeFixedAssetAction::class)->handle($asset->fresh(), $poster);
        $this->assertSame(AssetStatus::Active, $asset->fresh()->status);
        $this->assertSame(1, $company->journalEntries()->where('source_type', FixedAsset::class)->count());

        $period = $company->financialPeriods()->whereDate('starts_on', '2026-07-01')->firstOrFail();
        $run = DepreciationRun::query()->create([
            'company_id' => $company->getKey(), 'financial_period_id' => $period->getKey(),
            'depreciation_date' => '2026-07-31', 'prepared_by_id' => $maker->getKey(),
        ]);
        app(GenerateDepreciationRunAction::class)->handle($run, $maker);
        app(SubmitDepreciationRunAction::class)->handle($run, $maker);
        app(ApproveDepreciationRunAction::class)->handle($run, $approver);
        app(PostDepreciationRunAction::class)->handle($run, $poster);
        app(PostDepreciationRunAction::class)->handle($run->fresh(), $poster);
        $this->assertSame('9000.0000', $asset->fresh()->accumulated_depreciation);
        $this->assertSame(AssetAccountingStatus::Posted, $run->fresh()->status);

        $transfer = app(TransferFixedAssetAction::class)->handle(
            $asset->fresh(), $poster, CarbonImmutable::parse('2026-07-31'), 'Moved to main office',
            ['custodian_employment_id' => null, 'project_id' => null, 'project_site_id' => null, 'cost_center_id' => null, 'location' => 'Main Office'],
        );
        $this->assertSame('Main Office', $transfer->to_location);

        $disposal = AssetDisposal::query()->create([
            'company_id' => $company->getKey(), 'fixed_asset_id' => $asset->getKey(),
            'proceeds_account_id' => $company->accounts()->where('code', '1111')->value('id'),
            'disposal_date' => '2026-07-31', 'proceeds_amount' => '115000.0000',
            'cost_amount' => 0, 'accumulated_depreciation_amount' => 0, 'carrying_amount' => 0,
            'reason' => 'Replacement sale', 'prepared_by_id' => $maker->getKey(),
        ]);
        app(ApproveAssetDisposalAction::class)->handle($disposal, $approver);
        app(PostAssetDisposalAction::class)->handle($disposal, $poster);
        $this->assertSame(AssetStatus::Disposed, $asset->fresh()->status);
        $this->assertSame('4000.0000', $disposal->fresh()->gain_amount);

        app(ReverseAssetDisposalAction::class)->handle($disposal->fresh(), $poster, CarbonImmutable::parse('2026-07-31'), 'Sale cancelled');
        $this->assertSame(AssetStatus::Active, $asset->fresh()->status);
        $this->assertSame(AssetAccountingStatus::Reversed, $disposal->fresh()->status);

        $this->assertSame('FA-0001', app(FixedAssetRegisterReport::class)->forCompany($company)->sole()->asset_number);
        $reconciliation = app(FixedAssetReconciliationReport::class)->forCompany($company)->sole();
        $this->assertTrue($reconciliation['reconciled']);
        $this->assertSame('120000.0000', $reconciliation['register_cost']);
        $this->assertSame('9000.0000', $reconciliation['register_accumulated']);
    }

    /** @return array{Company, User, User, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-07-15'));
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, ...$users->all()];
    }
}
