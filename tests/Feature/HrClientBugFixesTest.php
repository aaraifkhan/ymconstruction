<?php

namespace Tests\Feature;

use App\Actions\HR\TransitionEmploymentMovementAction;
use App\Enums\CompensationStatus;
use App\Enums\EmploymentMovementStatus;
use App\Enums\EmploymentMovementType;
use App\Enums\EmploymentStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\EmploymentMovementRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrClientBugFixesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_bug1_photograph_path_is_fillable_and_persisted(): void
    {
        $employee = Employee::factory()->create([
            'photograph_path' => 'employees/photos/test-avatar.jpg',
        ]);

        $this->assertSame('employees/photos/test-avatar.jpg', $employee->fresh()->photograph_path);
    }

    public function test_bug2_reporting_to_relationship_is_persisted_and_resolved(): void
    {
        $company = Company::factory()->create();
        $manager = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
        ]);

        $subordinate = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-02-01',
            'reporting_to_employment_id' => $manager->getKey(),
        ]);

        $this->assertTrue($subordinate->reportingEmployment->is($manager));
        $this->assertTrue($manager->directReports->contains($subordinate));
    }

    public function test_bug3_joining_date_cannot_be_before_year_2000(): void
    {
        $company = Company::factory()->create();

        $this->expectException(ValidationException::class);

        Employment::factory()->forCompany($company)->create([
            'joining_date' => '1995-05-10',
        ]);
    }

    public function test_bug3_ending_date_cannot_be_before_probation_start(): void
    {
        $company = Company::factory()->create();

        $this->expectException(ValidationException::class);

        Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'probation_start_date' => '2026-07-01',
            'probation_end_date' => '2026-09-30',
            'employment_status' => EmploymentStatus::Terminated,
            'ending_date' => '2026-03-01', // ending before probation start!
        ]);
    }

    public function test_bug4_employee_infolist_resolves_active_employment_and_reporting_to(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);
        Filament::setTenant($company);

        $department = Department::factory()->for($company)->create(['name' => 'Architecture']);
        $designation = Designation::factory()->for($company)->create(['name' => 'Senior Architect']);

        $managerEmployee = Employee::factory()->create(['full_name' => 'John Manager']);
        $managerEmployment = Employment::factory()->forCompany($company)->for($managerEmployee)->create([
            'employee_code' => 'EMP-00001',
            'joining_date' => '2026-01-01',
        ]);

        $employee = Employee::factory()->create([
            'full_name' => 'Alice Subordinate',
            'photograph_path' => 'employees/photos/alice.jpg',
        ]);

        $employment = Employment::factory()->forCompany($company)->for($employee)->create([
            'employee_code' => 'EMP-00002',
            'department_id' => $department->getKey(),
            'designation_id' => $designation->getKey(),
            'reporting_to_employment_id' => $managerEmployment->getKey(),
            'joining_date' => '2026-02-01',
        ]);

        $activeEmployment = $employee->employments()->where('company_id', $company->getKey())->first();

        $this->assertNotNull($activeEmployment);
        $this->assertSame('EMP-00002', $activeEmployment->employee_code);
        $this->assertSame('Architecture', $activeEmployment->department->name);
        $this->assertSame('Senior Architect', $activeEmployment->designation->name);
        $this->assertSame('John Manager (EMP-00001)', "{$activeEmployment->reportingEmployment->employee->full_name} ({$activeEmployment->reportingEmployment->employee_code})");
    }

    public function test_bug5_designation_can_be_linked_to_department_and_validates_same_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $department = Department::factory()->for($company)->create(['name' => 'Engineering']);
        $otherDepartment = Department::factory()->for($otherCompany)->create(['name' => 'Other Eng']);

        $designation = Designation::factory()->for($company)->create([
            'name' => 'Software Engineer',
            'code' => 'SWE',
            'department_id' => $department->getKey(),
        ]);

        $this->assertTrue($designation->department->is($department));

        // Company-wide designation (null department) is also supported
        $ceoDesignation = Designation::factory()->for($company)->create([
            'name' => 'Chief Executive Officer',
            'code' => 'CEO',
            'department_id' => null,
        ]);
        $this->assertNull($ceoDesignation->department);

        // Validation fails if department belongs to a different company
        $this->expectException(ValidationException::class);
        Designation::factory()->for($company)->create([
            'name' => 'Cross Company Role',
            'code' => 'CCR',
            'department_id' => $otherDepartment->getKey(),
        ]);
    }

    public function test_bug6_employee_has_many_through_compensations_and_relation_works(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->forCompany($company)->for($employee)->create([
            'joining_date' => '2026-01-01',
        ]);

        $compensation = EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'basic_salary' => 150000,
            'house_travel_allowance' => 30000,
            'fuel_allowance' => 0,
            'mobile_allowance' => 0,
            'internet_allowance' => 0,
            'food_allowance' => 0,
            'site_allowance' => 0,
            'project_allowance' => 0,
            'other_allowance' => 0,
            'status' => CompensationStatus::Approved,
            'effective_from' => '2026-01-01',
        ]);

        $this->assertTrue($employee->compensations->contains($compensation));
        $this->assertSame(180000.0, $compensation->grossSalary());
    }

    public function test_bug7_promotion_approval_fails_with_validation_exception_on_maker_checker_violation(): void
    {
        $company = Company::factory()->create();
        $creator = User::factory()->create();
        $creator->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
        ]);

        $targetDesignation = Designation::factory()->for($company)->create();

        $movement = EmploymentMovementRequest::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'type' => EmploymentMovementType::Promotion,
            'status' => EmploymentMovementStatus::PendingApproval,
            'effective_on' => '2026-06-01',
            'target_designation_id' => $targetDesignation->getKey(),
            'created_by_id' => $creator->getKey(),
            'submitted_by_id' => $creator->getKey(),
        ]);

        Permission::findOrCreate('Approve:EmploymentMovementRequest', 'web');
        $creator->givePermissionTo('Approve:EmploymentMovementRequest');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Movement approval requires an independent approver.');

        app(TransitionEmploymentMovementAction::class)->approve($movement, $creator);
    }

    public function test_bug7_promotion_approval_succeeds_with_independent_approver(): void
    {
        $company = Company::factory()->create();
        $creator = User::factory()->create();
        $approver = User::factory()->create();
        $creator->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $approver->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
        ]);

        $targetDesignation = Designation::factory()->for($company)->create();

        $movement = EmploymentMovementRequest::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'type' => EmploymentMovementType::Promotion,
            'status' => EmploymentMovementStatus::PendingApproval,
            'effective_on' => '2026-06-01',
            'target_designation_id' => $targetDesignation->getKey(),
            'created_by_id' => $creator->getKey(),
            'submitted_by_id' => $creator->getKey(),
        ]);

        Permission::findOrCreate('Approve:EmploymentMovementRequest', 'web');
        Permission::findOrCreate('Apply:EmploymentMovementRequest', 'web');
        $approver->givePermissionTo(['Approve:EmploymentMovementRequest', 'Apply:EmploymentMovementRequest']);

        $approvedMovement = app(TransitionEmploymentMovementAction::class)->approve($movement, $approver);

        $this->assertSame(EmploymentMovementStatus::Applied, $approvedMovement->fresh()->status);
        $this->assertSame($approver->getKey(), $approvedMovement->fresh()->approved_by_id);
    }
}
