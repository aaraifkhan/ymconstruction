<?php

namespace Tests\Feature;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Employment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_cnic_is_normalized_encrypted_and_exactly_searchable_by_hash(): void
    {
        $employee = Employee::factory()->create(['cnic' => '35202-1234567-1']);

        $rawEmployee = DB::table('employees')->where('id', $employee->getKey())->first();

        $this->assertNotSame('3520212345671', $rawEmployee->cnic);
        $this->assertSame('3520212345671', $employee->fresh()->cnic);
        $this->assertSame(Employee::hashCnic('35202-1234567-1'), $rawEmployee->cnic_hash);
        $this->assertSame('•••••-•••••••-1', $employee->fresh()->maskedCnic());
    }

    public function test_employee_can_have_one_employment_in_each_of_multiple_companies(): void
    {
        $employee = Employee::factory()->create();
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        Employment::factory()->for($employee)->forCompany($firstCompany)->create([
            'employee_code' => 'EMP-001',
        ]);
        Employment::factory()->for($employee)->forCompany($secondCompany)->create([
            'employee_code' => 'EMP-001',
        ]);

        $this->assertCount(2, $employee->fresh()->employments);
        $this->assertTrue($employee->isEmployedBy($firstCompany));
        $this->assertTrue($employee->isEmployedBy($secondCompany));
    }

    public function test_department_designation_and_reporting_manager_must_belong_to_employment_company(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $otherDepartment = Department::factory()->for($otherCompany)->create();
        $otherDesignation = Designation::factory()->for($otherCompany)->create();
        $otherManager = Employment::factory()->forCompany($otherCompany)->create();

        foreach ([
            ['department_id' => $otherDepartment->getKey()],
            ['designation_id' => $otherDesignation->getKey()],
            ['reporting_to_employment_id' => $otherManager->getKey()],
        ] as $invalidReference) {
            try {
                Employment::factory()->forCompany($currentCompany)->create($invalidReference);
                $this->fail('A cross-company HR reference was accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_reporting_lines_are_traversable_and_cycles_are_rejected(): void
    {
        $company = Company::factory()->create();
        $director = Employment::factory()->forCompany($company)->create();
        $manager = Employment::factory()->forCompany($company)->create([
            'reporting_to_employment_id' => $director->getKey(),
        ]);
        $employee = Employment::factory()->forCompany($company)->create([
            'reporting_to_employment_id' => $manager->getKey(),
        ]);

        $this->assertTrue($employee->reportingEmployment->is($manager));
        $this->assertTrue($director->directReports->contains($manager));

        $this->expectException(ValidationException::class);

        $director->update(['reporting_to_employment_id' => $employee->getKey()]);
    }

    public function test_employment_uses_typed_status_category_and_validates_ending_date(): void
    {
        $employment = Employment::factory()->create([
            'employment_category' => EmploymentCategory::ProjectStaff,
            'employment_status' => EmploymentStatus::Probation,
        ]);

        $this->assertSame(EmploymentCategory::ProjectStaff, $employment->employment_category);
        $this->assertSame(EmploymentStatus::Probation, $employment->employment_status);

        $this->expectException(ValidationException::class);

        $employment->update([
            'joining_date' => '2026-07-10',
            'ending_date' => '2026-07-01',
        ]);
    }

    public function test_employee_activity_log_does_not_store_sensitive_personal_values(): void
    {
        $employee = Employee::factory()->create([
            'cnic' => '35202-7654321-9',
            'mobile' => '03001234567',
            'address' => 'Private employee address',
            'blood_group' => 'O+',
        ]);

        $properties = DB::table('activity_log')
            ->where('subject_type', Employee::class)
            ->where('subject_id', $employee->getKey())
            ->latest('id')
            ->value('properties');
        $serializedProperties = (string) $properties;

        $this->assertStringNotContainsString('3520276543219', $serializedProperties);
        $this->assertStringNotContainsString('03001234567', $serializedProperties);
        $this->assertStringNotContainsString('Private employee address', $serializedProperties);
        $this->assertStringNotContainsString('O+', $serializedProperties);
    }
}
