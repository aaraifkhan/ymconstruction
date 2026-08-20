<?php

namespace Tests\Feature;

use App\Enums\CompanyModuleState;
use App\Filament\Pages\DepartmentHeadDashboard;
use App\Filament\Pages\GlobalModulesAssignmentPage;
use App\Filament\Resources\CompanyModules\CompanyModuleResource;
use App\Filament\Widgets\GlobalCompanyModulesStatsWidget;
use App\Http\Middleware\EnsureTenantModuleAccess;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ModuleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompanyModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CompanySeeder::class);
    }

    public function test_module_seeder_registers_all_granular_modules_with_features(): void
    {
        $this->seed(ModuleSeeder::class);

        $expectedKeys = [
            'sm_department_operations',
            'hr',
            'accounts',
            'projects',
            'documents',
            'fixed_assets',
            'sales_crm',
            'purchases',
            'medical_billing',
        ];

        foreach ($expectedKeys as $key) {
            $module = Module::query()->where('key', $key)->first();
            $this->assertNotNull($module, "Module {$key} was not found");
            $this->assertTrue($module->is_active);
            $this->assertNotEmpty($module->features, "Module {$key} should have bundled features");
        }
    }

    public function test_company_module_enabled_state_resolution_and_inheritance(): void
    {
        $this->seed(ModuleSeeder::class);

        $parent = Company::query()->where('slug', 'bmc-construction')->firstOrFail();
        $child = Company::query()->where('slug', 'ymc-construction')->firstOrFail();
        $child->parent_company_id = $parent->id;
        $child->save();

        $module = Module::query()->where('key', 'sm_department_operations')->firstOrFail();

        // 1. By default, enabled
        $this->assertTrue($parent->hasModuleEnabled('sm_department_operations'));
        $this->assertTrue($child->hasModuleEnabled('sm_department_operations'));

        // 2. Explicitly disable on parent, set child to inherit
        CompanyModule::updateOrCreate(
            ['company_id' => $parent->id, 'module_id' => $module->id],
            ['state' => CompanyModuleState::Disabled],
        );
        CompanyModule::updateOrCreate(
            ['company_id' => $child->id, 'module_id' => $module->id],
            ['state' => CompanyModuleState::Inherit],
        );

        $this->assertFalse($parent->hasModuleEnabled('sm_department_operations'));
        $this->assertFalse($child->hasModuleEnabled('sm_department_operations'));

        // 3. Override child to explicitly enabled
        CompanyModule::updateOrCreate(
            ['company_id' => $child->id, 'module_id' => $module->id],
            ['state' => CompanyModuleState::Enabled],
        );

        $this->assertFalse($parent->hasModuleEnabled('sm_department_operations'));
        $this->assertTrue($child->hasModuleEnabled('sm_department_operations'));
    }

    public function test_super_admin_can_toggle_module_in_matrix_page(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $company = Company::query()->firstOrFail();
        $module = Module::query()->where('key', 'sm_department_operations')->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($company);

        Livewire::test(GlobalModulesAssignmentPage::class)
            ->assertSuccessful()
            ->call('toggleModuleState', $company->id, $module->id, 'disabled')
            ->assertSuccessful();

        $companyModule = CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($companyModule);
        $this->assertSame(CompanyModuleState::Disabled, $companyModule->state);
    }

    public function test_super_admin_can_update_status_via_in_table_select_column(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $company = Company::query()->firstOrFail();
        $module = Module::query()->where('key', 'hr')->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($company);

        $test = Livewire::test(GlobalModulesAssignmentPage::class)
            ->assertSuccessful()
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('navigation_group')
            ->assertTableColumnExists('company_'.$company->id)
            ->call('toggleModuleState', $company->id, $module->id, 'disabled')
            ->assertSuccessful();

        $companyModule = CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($companyModule);
        $this->assertSame(CompanyModuleState::Disabled, $companyModule->state);

        $test->assertTableColumnStateSet('company_'.$company->id, 'disabled', $module);
    }

    public function test_super_admin_can_update_status_for_different_tenant_company_without_tenant_scope_conflict(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $activeTenantCompany = Company::query()->firstOrFail();
        $targetCompany = Company::factory()->create(['name' => 'Target Sub Company', 'slug' => 'target-sub-company']);
        $module = Module::query()->where('key', 'medical_billing')->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($activeTenantCompany);

        // Pre-create record for the active tenant to test unique constraint would fail if hijacked
        CompanyModule::query()->updateOrCreate(
            ['company_id' => $activeTenantCompany->id, 'module_id' => $module->id],
            ['state' => CompanyModuleState::Enabled],
        );

        $test = Livewire::test(GlobalModulesAssignmentPage::class)
            ->assertSuccessful()
            ->call('toggleModuleState', $targetCompany->id, $module->id, 'disabled')
            ->assertSuccessful();

        $targetCompanyModule = CompanyModule::query()->withoutGlobalScopes()
            ->where('company_id', $targetCompany->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($targetCompanyModule);
        $this->assertSame(CompanyModuleState::Disabled, $targetCompanyModule->state);

        $activeTenantModule = CompanyModule::query()->withoutGlobalScopes()
            ->where('company_id', $activeTenantCompany->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertSame(CompanyModuleState::Enabled, $activeTenantModule->state);
    }

    public function test_super_admin_can_search_and_perform_global_actions_in_matrix_page(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $company = Company::query()->firstOrFail();
        $module = Module::query()->where('key', 'medical_billing')->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($company);

        Livewire::test(GlobalModulesAssignmentPage::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$module])
            ->searchTable('medical_billing')
            ->assertCanSeeTableRecords([$module])
            ->filterTable('navigation_group', 'Medical Billing')
            ->assertCanSeeTableRecords([$module])
            ->call('enableModuleForAllCompanies', $module->id)
            ->assertSuccessful()
            ->call('disableAllForCompany', $company->id)
            ->assertSuccessful()
            ->call('resetToInheritForCompany', $company->id)
            ->assertSuccessful();

        $companyModule = CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($companyModule);
        $this->assertSame(CompanyModuleState::Inherit, $companyModule->state);
    }

    public function test_global_company_modules_stats_widget_renders_metrics(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $this->actingAs($superAdmin);

        Livewire::test(GlobalCompanyModulesStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Active Companies')
            ->assertSee('System Modules')
            ->assertSee('Explicitly Enabled')
            ->assertSee('Explicitly Restricted');
    }

    public function test_company_module_resource_is_not_registered_in_sidebar_navigation(): void
    {
        $this->assertFalse(CompanyModuleResource::shouldRegisterNavigation());
    }

    public function test_disabled_module_page_is_blocked_by_middleware(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $company = Company::query()->firstOrFail();
        $module = Module::query()->where('key', 'sm_department_operations')->firstOrFail();

        // Explicitly disable module for company
        CompanyModule::updateOrCreate(
            ['company_id' => $company->id, 'module_id' => $module->id],
            ['state' => CompanyModuleState::Disabled],
        );

        $this->actingAs($superAdmin);

        $request = Request::create("/admin/company/{$company->slug}/department-head-dashboard");
        $route = new Route('GET', '/admin/company/{tenant}/department-head-dashboard', [
            'controller' => DepartmentHeadDashboard::class,
        ]);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        Filament::setTenant($company);

        $middleware = new EnsureTenantModuleAccess;

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_super_admin_can_access_global_modules_assignment_page_via_http(): void
    {
        $this->seed(ModuleSeeder::class);

        $superAdmin = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $this->actingAs($superAdmin);

        $response = $this->get('/super-admin/global-modules-assignment-page');
        $response->assertSuccessful();
    }
}
