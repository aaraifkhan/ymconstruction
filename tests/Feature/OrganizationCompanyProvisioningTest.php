<?php

namespace Tests\Feature;

use App\Enums\CompanyModuleState;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrganizationCompanyProvisioningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_provisions_the_four_independent_companies_and_module_governance(): void
    {
        $this->seed(CompanySeeder::class);

        $this->assertSame(4, Company::query()->count());
        $companies = Company::query()->orderBy('slug')->get();

        $this->assertSame([
            '7-orbit',
            '7-orbit-medical-billing',
            'bmc-construction',
            'ymc-construction',
        ], $companies->pluck('slug')->all());
        $this->assertTrue($companies->every(fn (Company $company): bool => $company->parent_company_id === null));
        $this->assertTrue($companies->every(fn (Company $company): bool => filled($company->logo_path)));

        $this->assertSame(4, Module::query()->count());
        $this->assertSame(16, CompanyModule::query()->count());

        Company::query()->each(function (Company $company): void {
            $this->assertTrue(
                $company->companyModules->every(
                    fn (CompanyModule $configuration): bool => $configuration->state === CompanyModuleState::Enabled,
                ),
            );
        });
    }

    public function test_provisioning_is_idempotent_and_preserves_existing_ym_data_and_custom_module_settings(): void
    {
        $existingYmcConstruction = Company::factory()->create([
            'name' => 'YMC Construction',
            'legal_name' => 'YMC Construction Custom Legal Name',
            'slug' => 'ymc-construction',
            'tax_number' => 'CUSTOM-NTN',
            'currency_code' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);

        $this->seed(CompanySeeder::class);

        $accountsModule = Module::query()->where('key', 'accounts')->firstOrFail();
        $accountsConfiguration = CompanyModule::query()
            ->whereBelongsTo($existingYmcConstruction)
            ->whereBelongsTo($accountsModule, 'module')
            ->firstOrFail();
        $accountsConfiguration->update([
            'state' => CompanyModuleState::Disabled,
            'variant' => 'custom',
            'settings' => ['reason' => 'preserve'],
        ]);

        $this->seed(CompanySeeder::class);

        $preservedYmcConstruction = Company::query()->where('slug', 'ymc-construction')->firstOrFail();

        $this->assertTrue($preservedYmcConstruction->is($existingYmcConstruction));
        $this->assertSame('YMC Construction Custom Legal Name', $preservedYmcConstruction->legal_name);
        $this->assertSame('CUSTOM-NTN', $preservedYmcConstruction->tax_number);
        $this->assertNull($preservedYmcConstruction->parent_company_id);
        $this->assertSame(4, Company::query()->count());
        $this->assertSame(16, CompanyModule::query()->count());

        $preservedConfiguration = $accountsConfiguration->fresh();

        $this->assertSame(CompanyModuleState::Disabled, $preservedConfiguration->state);
        $this->assertSame('custom', $preservedConfiguration->variant);
        $this->assertSame(['reason' => 'preserve'], $preservedConfiguration->settings);
    }

    public function test_company_access_requires_a_direct_active_membership(): void
    {
        $this->seed(CompanySeeder::class);

        $sevenOrbit = Company::query()->where('slug', '7-orbit')->firstOrFail();
        $sevenOrbitMedicalBilling = Company::query()
            ->where('slug', '7-orbit-medical-billing')
            ->firstOrFail();
        $user = User::factory()->create();

        $user->companies()->attach($sevenOrbit, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        $this->assertTrue($user->canAccessTenant($sevenOrbit));
        $this->assertFalse($user->canAccessTenant($sevenOrbitMedicalBilling));

        $user->companies()->updateExistingPivot($sevenOrbit, [
            'can_access_descendants' => true,
        ]);

        $this->assertFalse($user->canAccessTenant($sevenOrbitMedicalBilling));
        $this->assertFalse($user->canAccessTenant(
            Company::query()->where('slug', 'bmc-construction')->firstOrFail(),
        ));
    }
}
