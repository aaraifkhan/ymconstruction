<?php

namespace Tests\Feature;

use App\Enums\CompanyModuleState;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_company_hierarchy_is_traversable_and_circular_relationships_are_rejected(): void
    {
        $parentCompany = Company::factory()->create();
        $childCompany = Company::factory()->for($parentCompany, 'parentCompany')->create();

        $this->assertTrue($childCompany->parentCompany->is($parentCompany));
        $this->assertTrue($parentCompany->childCompanies->contains($childCompany));

        $this->expectException(ValidationException::class);

        $parentCompany->update(['parent_company_id' => $childCompany->getKey()]);
    }

    public function test_user_can_access_direct_companies_and_allowed_descendants_only(): void
    {
        $parentCompany = Company::factory()->create();
        $childCompany = Company::factory()->for($parentCompany, 'parentCompany')->create();
        $grandchildCompany = Company::factory()->for($childCompany, 'parentCompany')->create();
        $unrelatedCompany = Company::factory()->create();
        $user = User::factory()->create();

        $user->companies()->attach($parentCompany, [
            'is_active' => true,
            'can_access_descendants' => true,
        ]);

        $accessibleCompanyIds = $user->getAccessibleCompanies()->modelKeys();

        $this->assertContains($parentCompany->getKey(), $accessibleCompanyIds);
        $this->assertContains($childCompany->getKey(), $accessibleCompanyIds);
        $this->assertContains($grandchildCompany->getKey(), $accessibleCompanyIds);
        $this->assertNotContains($unrelatedCompany->getKey(), $accessibleCompanyIds);
        $this->assertTrue($user->canAccessTenant($grandchildCompany));
        $this->assertFalse($user->canAccessTenant($unrelatedCompany));
    }

    public function test_inactive_memberships_and_inactive_companies_do_not_grant_access(): void
    {
        $inactiveMembershipCompany = Company::factory()->create();
        $inactiveCompany = Company::factory()->inactive()->create();
        $user = User::factory()->create();

        $user->companies()->attach($inactiveMembershipCompany, [
            'is_active' => false,
            'can_access_descendants' => false,
        ]);
        $user->companies()->attach($inactiveCompany, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        $this->assertTrue($user->getAccessibleCompanies()->isEmpty());
        $this->assertFalse($user->canAccessTenant($inactiveMembershipCompany));
        $this->assertFalse($user->canAccessTenant($inactiveCompany));
    }

    public function test_bank_identifiers_are_encrypted_and_only_one_payroll_default_remains(): void
    {
        $company = Company::factory()->create();
        $firstAccount = CompanyBankAccount::factory()
            ->for($company)
            ->create([
                'account_number' => '12345678901234',
                'iban' => 'PK00TEST1234567890123456',
                'is_default_for_payroll' => true,
            ]);
        $secondAccount = CompanyBankAccount::factory()
            ->for($company)
            ->create(['is_default_for_payroll' => true]);

        $rawAccountNumber = DB::table((new CompanyBankAccount)->getTable())
            ->where('id', $firstAccount->getKey())
            ->value('account_number');

        $this->assertNotSame('12345678901234', $rawAccountNumber);
        $this->assertSame('12345678901234', $firstAccount->fresh()->account_number);
        $this->assertFalse($firstAccount->fresh()->is_default_for_payroll);
        $this->assertTrue($secondAccount->fresh()->is_default_for_payroll);
    }

    public function test_company_module_configuration_uses_typed_state(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();
        $companyModule = CompanyModule::factory()
            ->for($company)
            ->for($module)
            ->enabled()
            ->create(['settings' => ['approval_required' => true]]);

        $this->assertSame(CompanyModuleState::Enabled, $companyModule->state);
        $this->assertSame(['approval_required' => true], $companyModule->settings);
        $this->assertTrue($company->companyModules->contains($companyModule));
    }
}
