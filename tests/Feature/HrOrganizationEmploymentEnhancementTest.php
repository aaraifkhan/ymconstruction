<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCodeSequence;
use App\Models\Employment;
use App\Models\User;
use App\Models\WorkLocation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrOrganizationEmploymentEnhancementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_employee_codes_are_automatic_collision_safe_and_independent_per_company(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        Employment::factory()->forCompany($firstCompany)->create(['employee_code' => 'EMP-00001']);

        $firstAutomatic = Employment::factory()
            ->for(Employee::factory())
            ->forCompany($firstCompany)
            ->create(['employee_code' => null]);
        $secondAutomatic = Employment::factory()
            ->for(Employee::factory())
            ->forCompany($firstCompany)
            ->create(['employee_code' => null]);
        $otherCompanyAutomatic = Employment::factory()
            ->for(Employee::factory())
            ->forCompany($secondCompany)
            ->create(['employee_code' => null]);

        $this->assertSame('EMP-00002', $firstAutomatic->employee_code);
        $this->assertSame('EMP-00003', $secondAutomatic->employee_code);
        $this->assertSame('EMP-00001', $otherCompanyAutomatic->employee_code);
        $this->assertSame('EMP-00001', Employment::query()->where('company_id', $firstCompany->getKey())->oldest()->value('employee_code'));
    }

    public function test_department_parent_must_be_same_company_and_hierarchy_cannot_cycle(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $root = Department::factory()->for($company)->create();
        $child = Department::factory()->for($company)->create(['parent_department_id' => $root->getKey()]);
        $otherDepartment = Department::factory()->for($otherCompany)->create();

        $this->expectValidationException(function () use ($child, $otherDepartment): void {
            $child->update(['parent_department_id' => $otherDepartment->getKey()]);
        });

        $this->expectValidationException(function () use ($root, $child): void {
            $root->update(['parent_department_id' => $child->getKey()]);
        });

        $this->assertTrue($child->fresh()->parentDepartment->is($root));
    }

    public function test_employment_lifecycle_fields_and_company_owned_work_location_are_validated(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $workLocation = WorkLocation::factory()->for($company)->create();
        $otherWorkLocation = WorkLocation::factory()->for($otherCompany)->create();

        $employment = Employment::factory()->forCompany($company)->create([
            'employment_type' => EmploymentType::Contract,
            'employment_status' => EmploymentStatus::Active,
            'work_location_id' => $workLocation->getKey(),
            'joining_date' => '2026-01-01',
            'probation_start_date' => '2026-01-01',
            'probation_end_date' => '2026-03-31',
            'confirmation_date' => '2026-04-01',
            'notice_period_days' => 30,
        ]);

        $this->assertSame(EmploymentType::Contract, $employment->employment_type);
        $this->assertTrue($employment->workLocation->is($workLocation));

        $this->expectValidationException(function () use ($company, $otherWorkLocation): void {
            Employment::factory()->forCompany($company)->create([
                'work_location_id' => $otherWorkLocation->getKey(),
            ]);
        });

        $this->expectValidationException(function () use ($company): void {
            Employment::factory()->forCompany($company)->create([
                'employment_status' => EmploymentStatus::Resigned,
                'ending_date' => null,
            ]);
        });

        $this->expectValidationException(function () use ($company): void {
            Employment::factory()->forCompany($company)->create([
                'joining_date' => '2026-01-01',
                'employment_status' => EmploymentStatus::Probation,
                'confirmation_date' => '2026-02-01',
            ]);
        });
    }

    public function test_employment_changes_keep_immutable_before_and_after_snapshots(): void
    {
        $company = Company::factory()->create();
        $firstDepartment = Department::factory()->for($company)->create();
        $secondDepartment = Department::factory()->for($company)->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'department_id' => $firstDepartment->getKey(),
        ]);

        $createdChange = $employment->changes()->where('event_type', 'created')->sole();
        $this->assertNull($createdChange->before_snapshot);
        $this->assertSame($firstDepartment->getKey(), $createdChange->after_snapshot['department_id']);

        $employment->update(['department_id' => $secondDepartment->getKey()]);

        $updatedChange = $employment->changes()->where('event_type', 'updated')->sole();
        $this->assertContains('department_id', $updatedChange->changed_fields);
        $this->assertSame($firstDepartment->getKey(), $updatedChange->before_snapshot['department_id']);
        $this->assertSame($secondDepartment->getKey(), $updatedChange->after_snapshot['department_id']);

        $this->expectException(LogicException::class);
        $updatedChange->update(['event_type' => 'altered']);
    }

    public function test_new_hr_masters_and_history_are_tenant_scoped_and_permission_protected(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $workLocation = WorkLocation::factory()->for($company)->create();
        $otherWorkLocation = WorkLocation::factory()->for($otherCompany)->create();
        $employmentChange = Employment::factory()->forCompany($company)->create()->changes()->firstOrFail();
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo([
            Permission::findOrCreate('View:WorkLocation'),
            Permission::findOrCreate('ViewAny:WorkLocation'),
            Permission::findOrCreate('View:EmploymentChange'),
            Permission::findOrCreate('ViewAny:EmploymentChange'),
            Permission::findOrCreate('Create:EmployeeCodeSequence'),
        ]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        $this->assertTrue(Gate::allows('view', $workLocation));
        $this->assertFalse(Gate::allows('view', $otherWorkLocation));
        $this->assertTrue(Gate::allows('view', $employmentChange));
        $this->assertFalse(Gate::allows('update', $employmentChange));
        $this->assertTrue(Gate::allows('create', EmployeeCodeSequence::class));

        EmployeeCodeSequence::query()->create([
            'company_id' => $company->getKey(),
            'prefix' => 'EMP',
            'padding' => 5,
            'next_number' => 1,
        ]);

        $this->assertFalse(Gate::allows('create', EmployeeCodeSequence::class));
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
