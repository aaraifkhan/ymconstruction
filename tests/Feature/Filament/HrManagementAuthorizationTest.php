<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employments\EmploymentResource;
use App\Filament\Resources\Employments\Pages\CreateEmployment;
use App\Filament\Resources\WorkLocations\WorkLocationResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\User;
use App\Models\WorkLocation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrManagementAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_hr_resource_queries_are_scoped_to_current_company(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $currentDepartment = Department::factory()->for($currentCompany)->create();
        $otherDepartment = Department::factory()->for($otherCompany)->create();
        $currentEmployment = Employment::factory()->forCompany($currentCompany)->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();
        $currentWorkLocation = WorkLocation::factory()->for($currentCompany)->create();
        $otherWorkLocation = WorkLocation::factory()->for($otherCompany)->create();
        $user = $this->userWithCompany($currentCompany, [
            'ViewAny:Department',
            'ViewAny:Employee',
            'ViewAny:Employment',
            'ViewAny:WorkLocation',
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($currentCompany);

        $this->assertSame([$currentDepartment->getKey()], DepartmentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$currentEmployment->getKey()], EmploymentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$currentWorkLocation->getKey()], WorkLocationResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$currentEmployment->employee_id], EmployeeResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherDepartment->getKey(), DepartmentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherEmployment->employee_id, EmployeeResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherWorkLocation->getKey(), WorkLocationResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_sensitive_employee_capabilities_have_separate_permissions(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $employee = $employment->employee;
        $user = $this->userWithCompany($company, ['View:Employee']);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        $this->assertTrue(Gate::allows('view', $employee));
        $this->assertFalse(Gate::allows('viewIdentity', $employee));
        $this->assertFalse(Gate::allows('viewContact', $employee));
        $this->assertFalse(Gate::allows('viewMedical', $employee));
        $this->assertFalse(Gate::allows('manageSensitive', $employee));

        $user->givePermissionTo([
            Permission::findOrCreate('ViewIdentity:Employee'),
            Permission::findOrCreate('ViewContact:Employee'),
            Permission::findOrCreate('ViewMedical:Employee'),
            Permission::findOrCreate('ManageSensitive:Employee'),
        ]);

        $this->assertTrue(Gate::allows('viewIdentity', $employee));
        $this->assertTrue(Gate::allows('viewContact', $employee));
        $this->assertTrue(Gate::allows('viewMedical', $employee));
        $this->assertTrue(Gate::allows('manageSensitive', $employee));
    }

    public function test_creating_employee_creates_initial_employment_in_active_company(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->for($company)->create();
        $user = $this->userWithCompany($company, [
            'Create:Employee',
            'Create:Employment',
            'ViewAny:Employee',
            'View:Employee',
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'full_name' => 'Ali Raza',
                'is_active' => true,
                'employment_joining_date' => '2026-07-24',
                'employment_department_id' => $department->getKey(),
                'employment_employment_category' => 'administrative_staff',
                'employment_employment_status' => 'probation',
                'employment_working_days_per_week' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::query()->where('full_name', 'Ali Raza')->firstOrFail();
        $employment = $employee->employments()->firstOrFail();

        $this->assertTrue($employment->company->is($company));
        $this->assertSame('EMP-00001', $employment->employee_code);
        $this->assertTrue($employment->department->is($department));
    }

    public function test_existing_employee_can_be_associated_with_another_company(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $employee = Employment::factory()->forCompany($firstCompany)->create()->employee;
        $user = $this->userWithCompany($secondCompany, [
            'Create:Employment',
            'ViewAny:Employment',
            'View:Employment',
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($secondCompany);

        Livewire::test(CreateEmployment::class)
            ->fillForm([
                'employee_id' => $employee->getKey(),
                'joining_date' => '2026-07-24',
                'employment_category' => 'project_staff',
                'employment_status' => 'active',
                'working_days_per_week' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employments', [
            'company_id' => $secondCompany->getKey(),
            'employee_id' => $employee->getKey(),
            'employee_code' => 'EMP-00001',
        ]);

        $this->assertDatabaseHas('employments', [
            'employee_id' => $employee->getKey(),
            'company_id' => $firstCompany->getKey(),
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('employments', [
            'employee_id' => $employee->getKey(),
            'company_id' => $secondCompany->getKey(),
            'employee_code' => 'EMP-00001',
            'deleted_at' => null,
        ]);
    }

    public function test_department_page_creates_record_in_current_company(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithCompany($company, [
            'Create:Department',
            'ViewAny:Department',
            'View:Department',
        ]);

        $this->actingAs($user);
        $this->bootCompanyTenant($company);

        Livewire::test(CreateDepartment::class)
            ->fillForm([
                'name' => 'Human Resources',
                'code' => 'HR',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'company_id' => $company->getKey(),
            'name' => 'Human Resources',
            'code' => 'HR',
        ]);
    }

    public function test_employee_list_only_shows_employees_of_current_company(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $currentEmployee = Employment::factory()->forCompany($currentCompany)->create()->employee;
        $otherEmployee = Employment::factory()->forCompany($otherCompany)->create()->employee;
        $user = $this->userWithCompany($currentCompany, ['ViewAny:Employee', 'View:Employee']);

        $this->actingAs($user);
        $this->bootCompanyTenant($currentCompany);

        Livewire::test(ListEmployees::class)
            ->assertCanSeeTableRecords([$currentEmployee])
            ->assertCanNotSeeTableRecords([$otherEmployee]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithCompany(Company $company, array $permissions): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission))
                ->all(),
        );

        return $user;
    }

    private function bootCompanyTenant(Company $company): void
    {
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
