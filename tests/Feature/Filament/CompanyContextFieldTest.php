<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionForm;
use App\Filament\Resources\AttendancePunches\Schemas\AttendancePunchForm;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordForm;
use App\Filament\Resources\EmployeeFinancings\Schemas\EmployeeFinancingForm;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Resources\ShiftAssignments\Schemas\ShiftAssignmentForm;
use App\Filament\Support\CompanyContextField;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employment;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompanyContextFieldTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_company_context_field_defaults_to_active_tenant_and_is_locked(): void
    {
        $company = Company::factory()->create(['name' => '7-Orbit']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);
        Filament::setTenant($company);

        $field = CompanyContextField::make();

        $this->assertInstanceOf(Select::class, $field);
        $this->assertSame('company_id', $field->getName());
        $this->assertSame('Company', $field->getLabel());
        $this->assertTrue($field->isDisabled());
        $this->assertTrue($field->isDehydrated());
        $this->assertTrue($field->isRequired());
        $this->assertSame($company->getKey(), $field->getDefaultState());
    }

    public function test_company_context_field_dynamically_updates_when_tenant_switches(): void
    {
        $firstCompany = Company::factory()->create(['name' => 'Company One']);
        $secondCompany = Company::factory()->create(['name' => 'Company Two']);
        $user = User::factory()->create();
        $user->companies()->attach([$firstCompany->getKey(), $secondCompany->getKey()], ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);

        Filament::setTenant($firstCompany);
        $field1 = CompanyContextField::make();
        $this->assertSame($firstCompany->getKey(), $field1->getDefaultState());

        Filament::setTenant($secondCompany);
        $field2 = CompanyContextField::make();
        $this->assertSame($secondCompany->getKey(), $field2->getDefaultState());
    }

    public function test_hr_form_schemas_include_locked_company_context_field(): void
    {
        $company = Company::factory()->create(['name' => '7-Orbit']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);
        Filament::setTenant($company);

        $schemas = [
            AttendanceCorrectionForm::class,
            AttendancePunchForm::class,
            AttendanceRecordForm::class,
            EmployeeFinancingForm::class,
            LeaveRequestForm::class,
            ShiftAssignmentForm::class,
        ];

        foreach ($schemas as $formClass) {
            $schema = $formClass::configure(Schema::make());
            $components = collect($schema->getComponents());
            /** @var Select|null $companyField */
            $companyField = $components->first(fn ($component): bool => $component instanceof Select && $component->getName() === 'company_id');

            $this->assertNotNull($companyField, "Failed asserting that {$formClass} contains company_id select component.");
            $this->assertTrue($companyField->isDisabled(), "Failed asserting that company_id is disabled in {$formClass}.");
            $this->assertTrue($companyField->isDehydrated(), "Failed asserting that company_id is dehydrated in {$formClass}.");
            $this->assertSame($company->getKey(), $companyField->getDefaultState(), "Failed asserting default state matches active tenant in {$formClass}.");
        }
    }

    public function test_dependent_dropdowns_in_attendance_correction_form_are_scoped_to_active_tenant(): void
    {
        $currentCompany = Company::factory()->create(['name' => 'Current Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);

        $currentEmployment = Employment::factory()->forCompany($currentCompany)->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();

        $currentRecord = AttendanceRecord::factory()->create([
            'company_id' => $currentCompany->getKey(),
            'employment_id' => $currentEmployment->getKey(),
            'attendance_date' => '2026-08-01',
        ]);
        $otherRecord = AttendanceRecord::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'employment_id' => $otherEmployment->getKey(),
            'attendance_date' => '2026-08-01',
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($currentCompany, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);
        Filament::setTenant($currentCompany);

        $schema = AttendanceCorrectionForm::configure(Schema::make());
        $components = collect($schema->getComponents());
        /** @var Select $recordField */
        $recordField = $components->first(fn ($component): bool => $component instanceof Select && $component->getName() === 'attendance_record_id');

        $options = $recordField->getOptions();
        $this->assertArrayHasKey($currentRecord->getKey(), $options);
        $this->assertArrayNotHasKey($otherRecord->getKey(), $options);
    }

    public function test_dependent_dropdowns_in_leave_request_form_are_scoped_to_active_tenant(): void
    {
        $currentCompany = Company::factory()->create(['name' => 'Current Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);

        $currentEmployment = Employment::factory()->forCompany($currentCompany)->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();

        $user = User::factory()->create();
        $user->companies()->attach($currentCompany, ['is_active' => true, 'can_access_descendants' => false]);
        $this->actingAs($user);
        Filament::setTenant($currentCompany);

        $schema = LeaveRequestForm::configure(Schema::make());
        $components = collect($schema->getComponents());
        /** @var Select $employmentField */
        $employmentField = $components->first(fn ($component): bool => $component instanceof Select && $component->getName() === 'employment_id');

        $options = $employmentField->getOptions();
        $this->assertArrayHasKey($currentEmployment->getKey(), $options);
        $this->assertArrayNotHasKey($otherEmployment->getKey(), $options);
    }
}
