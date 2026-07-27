<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Filament\Pages\FixedAssetReports;
use App\Filament\Resources\FixedAssets\Pages\ListFixedAssets;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FixedAssetAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_asset_register_and_reports_are_tenant_scoped(): void
    {
        [$company, $asset] = $this->draftAsset('FA-A');
        [, $otherAsset] = $this->draftAsset('FA-B');
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        foreach (['ViewAny:FixedAsset', 'View:FixedAsset', 'View:FixedAssetReports'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(ListFixedAssets::class)
            ->assertCanSeeTableRecords([$asset])
            ->assertCanNotSeeTableRecords([$otherAsset]);
        Livewire::test(FixedAssetReports::class)->assertSuccessful();
    }

    /** @return array{Company, FixedAsset} */
    private function draftAsset(string $number): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $category = AssetCategory::query()->create([
            'company_id' => $company->getKey(),
            'code' => 'COMP',
            'name' => 'Computers',
            'cost_account_id' => $company->accounts()->where('code', '1240')->value('id'),
            'accumulated_depreciation_account_id' => $company->accounts()->where('code', '1290')->value('id'),
            'depreciation_expense_account_id' => $company->accounts()->where('code', '6100')->value('id'),
            'default_useful_life_months' => 36,
        ]);
        $asset = FixedAsset::query()->create([
            'company_id' => $company->getKey(),
            'asset_category_id' => $category->getKey(),
            'capitalization_credit_account_id' => $company->accounts()->where('code', '1111')->value('id'),
            'asset_number' => $number,
            'name' => 'Laptop',
            'acquired_on' => '2026-07-01',
            'available_for_use_on' => '2026-07-01',
            'acquisition_cost' => 100000,
            'residual_value' => 10000,
            'useful_life_months' => 36,
            'prepared_by_id' => User::factory()->create()->getKey(),
        ]);

        return [$company, $asset];
    }
}
