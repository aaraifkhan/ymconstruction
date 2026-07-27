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

    public function test_seeder_provisions_confirmed_company_topology_and_module_governance(): void
    {
        $this->seed(CompanySeeder::class);

        $this->assertSame(6, Company::query()->count());

        $sevenOrbit = Company::query()->where('slug', '7-orbit')->firstOrFail();
        $sevenOrbitIt = Company::query()->where('slug', '7-orbit-it')->firstOrFail();
        $sevenOrbitMedicalBilling = Company::query()
            ->where('slug', '7-orbit-medical-billing')
            ->firstOrFail();

        $this->assertNull($sevenOrbit->parent_company_id);
        $this->assertTrue($sevenOrbitIt->parentCompany->is($sevenOrbit));
        $this->assertTrue($sevenOrbitMedicalBilling->parentCompany->is($sevenOrbit));
        $this->assertNull(Company::query()->where('slug', 'ym-construction')->firstOrFail()->parent_company_id);
        $this->assertNull(Company::query()->where('slug', 'bmc')->firstOrFail()->parent_company_id);
        $this->assertNull(Company::query()->where('slug', 'bmc-trading')->firstOrFail()->parent_company_id);

        $this->assertSame(4, Module::query()->count());
        $this->assertSame(24, CompanyModule::query()->count());

        Company::query()
            ->whereNull('parent_company_id')
            ->each(function (Company $company): void {
                $this->assertTrue(
                    $company->companyModules->every(
                        fn (CompanyModule $configuration): bool => $configuration->state === CompanyModuleState::Enabled,
                    ),
                );
            });

        foreach ([$sevenOrbitIt, $sevenOrbitMedicalBilling] as $childCompany) {
            $this->assertTrue(
                $childCompany->companyModules->every(
                    fn (CompanyModule $configuration): bool => $configuration->state === CompanyModuleState::Inherit,
                ),
            );
        }
    }

    public function test_provisioning_is_idempotent_and_preserves_existing_ym_data_and_custom_module_settings(): void
    {
        $existingYmConstruction = Company::factory()->create([
            'name' => 'YM Construction',
            'legal_name' => 'YM Construction Custom Legal Name',
            'slug' => 'ym-construction',
            'tax_number' => 'CUSTOM-NTN',
            'currency_code' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);

        $this->seed(CompanySeeder::class);

        $accountsModule = Module::query()->where('key', 'accounts')->firstOrFail();
        $accountsConfiguration = CompanyModule::query()
            ->whereBelongsTo($existingYmConstruction)
            ->whereBelongsTo($accountsModule, 'module')
            ->firstOrFail();
        $accountsConfiguration->update([
            'state' => CompanyModuleState::Disabled,
            'variant' => 'custom',
            'settings' => ['reason' => 'preserve'],
        ]);

        $this->seed(CompanySeeder::class);

        $preservedYmConstruction = Company::query()->where('slug', 'ym-construction')->firstOrFail();

        $this->assertTrue($preservedYmConstruction->is($existingYmConstruction));
        $this->assertSame('YM Construction Custom Legal Name', $preservedYmConstruction->legal_name);
        $this->assertSame('CUSTOM-NTN', $preservedYmConstruction->tax_number);
        $this->assertNull($preservedYmConstruction->parent_company_id);
        $this->assertSame(6, Company::query()->count());
        $this->assertSame(24, CompanyModule::query()->count());

        $preservedConfiguration = $accountsConfiguration->fresh();

        $this->assertSame(CompanyModuleState::Disabled, $preservedConfiguration->state);
        $this->assertSame('custom', $preservedConfiguration->variant);
        $this->assertSame(['reason' => 'preserve'], $preservedConfiguration->settings);
    }

    public function test_descendant_company_access_remains_explicit_per_membership(): void
    {
        $this->seed(CompanySeeder::class);

        $sevenOrbit = Company::query()->where('slug', '7-orbit')->firstOrFail();
        $sevenOrbitIt = Company::query()->where('slug', '7-orbit-it')->firstOrFail();
        $sevenOrbitMedicalBilling = Company::query()
            ->where('slug', '7-orbit-medical-billing')
            ->firstOrFail();
        $user = User::factory()->create();

        $user->companies()->attach($sevenOrbit, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        $this->assertTrue($user->canAccessTenant($sevenOrbit));
        $this->assertFalse($user->canAccessTenant($sevenOrbitIt));
        $this->assertFalse($user->canAccessTenant($sevenOrbitMedicalBilling));

        $user->companies()->updateExistingPivot($sevenOrbit, [
            'can_access_descendants' => true,
        ]);

        $this->assertTrue($user->canAccessTenant($sevenOrbitIt));
        $this->assertTrue($user->canAccessTenant($sevenOrbitMedicalBilling));
        $this->assertFalse($user->canAccessTenant(
            Company::query()->where('slug', 'bmc')->firstOrFail(),
        ));
    }
}
