<?php

namespace Tests\Feature\Filament;

use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Actions\JoiningLetters\ProvisionDefaultJoiningLetterTemplateAction;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\CompanyBankAccounts\CompanyBankAccountResource;
use App\Filament\Resources\CompanyBankAccounts\Pages\CreateCompanyBankAccount;
use App\Filament\Resources\CompanyBankAccounts\Pages\ListCompanyBankAccounts;
use App\Filament\Resources\CompanyModules\Pages\CreateCompanyModule;
use App\Filament\Resources\CompanyModules\Pages\ListCompanyModules;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyTenantAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_company_resource_query_only_contains_companies_accessible_to_user(): void
    {
        $accessibleCompany = Company::factory()->create();
        $inaccessibleCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($accessibleCompany, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($accessibleCompany);

        $companyIds = CompanyResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($accessibleCompany->getKey(), $companyIds);
        $this->assertNotContains($inaccessibleCompany->getKey(), $companyIds);
    }

    public function test_bank_account_resource_and_policy_are_scoped_to_current_company(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $currentAccount = CompanyBankAccount::factory()->for($currentCompany)->create();
        $otherAccount = CompanyBankAccount::factory()->for($otherCompany)->create();
        $user = User::factory()->create();
        $user->companies()->attach($currentCompany, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:CompanyBankAccount'));
        $user->givePermissionTo(Permission::findOrCreate('View:CompanyBankAccount'));

        $this->actingAs($user);
        $this->bootCompanyTenant($currentCompany);

        $accountIds = CompanyBankAccountResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($currentAccount->getKey(), $accountIds);
        $this->assertNotContains($otherAccount->getKey(), $accountIds);
        $this->assertTrue(CompanyBankAccountResource::canViewAny());
        $this->assertTrue(Gate::allows('view', $currentAccount));
        $this->assertFalse(Gate::allows('view', $otherAccount));
    }

    public function test_permission_without_company_membership_cannot_open_company_resources(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:CompanyBankAccount'));

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        $this->assertFalse($user->canAccessTenant($company));
        $this->assertFalse(CompanyBankAccountResource::canViewAny());
    }

    public function test_sensitive_bank_details_require_their_own_permission(): void
    {
        $company = Company::factory()->create();
        $account = CompanyBankAccount::factory()->for($company)->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        $this->assertFalse(Gate::allows('viewSensitive', $account));

        $user->givePermissionTo(Permission::findOrCreate('ViewSensitive:CompanyBankAccount'));

        $this->assertTrue(Gate::allows('viewSensitive', $account));
    }

    public function test_bank_account_filament_pages_render_and_create_inside_current_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $existingAccount = CompanyBankAccount::factory()->for($company)->create();
        $otherAccount = CompanyBankAccount::factory()->for($otherCompany)->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo([
            Permission::findOrCreate('ViewAny:CompanyBankAccount'),
            Permission::findOrCreate('View:CompanyBankAccount'),
            Permission::findOrCreate('Create:CompanyBankAccount'),
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        Livewire::test(ListCompanyBankAccounts::class)
            ->assertCanSeeTableRecords([$existingAccount])
            ->assertCanNotSeeTableRecords([$otherAccount]);

        Livewire::test(CreateCompanyBankAccount::class)
            ->fillForm([
                'bank_name' => 'Meezan Bank',
                'branch_name' => 'Main Branch',
                'account_title' => 'Current Company',
                'account_number' => '1234567890',
                'currency_code' => 'PKR',
                'account_type' => 'current',
                'is_default_for_payroll' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdAccount = CompanyBankAccount::query()
            ->where('bank_name', 'Meezan Bank')
            ->firstOrFail();

        $this->assertTrue($createdAccount->company->is($company));
    }

    public function test_company_and_module_configuration_pages_render_in_tenant_context(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();
        $companyModule = CompanyModule::factory()->for($company)->for($module)->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo([
            Permission::findOrCreate('ViewAny:Company'),
            Permission::findOrCreate('View:Company'),
            Permission::findOrCreate('ViewAny:CompanyModule'),
            Permission::findOrCreate('View:CompanyModule'),
            Permission::findOrCreate('Create:CompanyModule'),
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        Livewire::test(ListCompanies::class)
            ->assertCanSeeTableRecords([$company]);

        Livewire::test(ListCompanyModules::class)
            ->assertCanSeeTableRecords([$companyModule]);

        Livewire::test(CreateCompanyModule::class)
            ->assertFormFieldExists('module_id')
            ->assertFormFieldExists('state');
    }

    public function test_creating_a_company_grants_creator_access_and_provisions_document_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $createdCompany = Company::factory()->create();
        $page = app(CreateCompany::class);
        $page->record = $createdCompany;
        $page->boot(
            app(ProvisionDefaultDocumentCategoriesAction::class),
            app(ProvisionDefaultJoiningLetterTemplateAction::class),
        );

        $afterCreate = new ReflectionMethod($page, 'afterCreate');
        $afterCreate->invoke($page);

        $this->assertTrue($user->canAccessTenant($createdCompany));
        $this->assertSame(5, $createdCompany->documentCategories()->count());
        $this->assertSame(1, $createdCompany->joiningLetterTemplates()->count());
    }

    private function bootCompanyTenant(Company $company): void
    {
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
