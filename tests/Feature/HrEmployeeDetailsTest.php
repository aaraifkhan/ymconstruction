<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeExperience;
use App\Models\EmployeeQualification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrEmployeeDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_has_repeatable_emergency_qualification_experience_and_bank_records(): void
    {
        $employee = Employee::factory()->create();
        $contact = EmployeeEmergencyContact::factory()->for($employee)->create();
        $qualification = EmployeeQualification::factory()->for($employee)->create();
        $experience = EmployeeExperience::factory()->for($employee)->create();
        $bankAccount = EmployeeBankAccount::factory()->for($employee)->create();

        $employee->refresh();

        $this->assertTrue($employee->emergencyContacts->contains($contact));
        $this->assertTrue($employee->qualifications->contains($qualification));
        $this->assertTrue($employee->experiences->contains($experience));
        $this->assertTrue($employee->bankAccounts->contains($bankAccount));
    }

    public function test_employee_bank_identifiers_are_encrypted_masked_and_excluded_from_serialization(): void
    {
        $bankAccount = EmployeeBankAccount::factory()->create([
            'account_number' => '12345678901234',
            'iban' => 'PK00TEST1234567890123456',
        ]);

        $rawBankAccount = DB::table((new EmployeeBankAccount)->getTable())
            ->where('id', $bankAccount->getKey())
            ->first();

        $this->assertNotSame('12345678901234', $rawBankAccount->account_number);
        $this->assertNotSame('PK00TEST1234567890123456', $rawBankAccount->iban);
        $this->assertSame('12345678901234', $bankAccount->fresh()->account_number);
        $this->assertSame('•••• 1234', $bankAccount->fresh()->maskedAccountNumber());
        $this->assertSame('•••• 3456', $bankAccount->fresh()->maskedIban());
        $this->assertArrayNotHasKey('account_number', $bankAccount->fresh()->toArray());
        $this->assertArrayNotHasKey('iban', $bankAccount->fresh()->toArray());
    }

    public function test_only_one_primary_emergency_contact_and_payroll_bank_account_remains(): void
    {
        $employee = Employee::factory()->create();
        $firstContact = EmployeeEmergencyContact::factory()->primary()->for($employee)->create();
        $secondContact = EmployeeEmergencyContact::factory()->primary()->for($employee)->create();
        $firstBank = EmployeeBankAccount::factory()->payrollDefault()->for($employee)->create();
        $secondBank = EmployeeBankAccount::factory()->payrollDefault()->for($employee)->create();

        $this->assertFalse($firstContact->fresh()->is_primary);
        $this->assertTrue($secondContact->fresh()->is_primary);
        $this->assertFalse($firstBank->fresh()->is_primary_for_payroll);
        $this->assertTrue($secondBank->fresh()->is_primary_for_payroll);
    }

    public function test_previous_experience_rejects_an_end_date_before_its_start_date(): void
    {
        $this->expectException(ValidationException::class);

        EmployeeExperience::factory()->create([
            'start_date' => '2025-02-01',
            'end_date' => '2025-01-31',
        ]);
    }

    public function test_sensitive_contact_and_bank_values_are_not_written_to_activity_properties(): void
    {
        $employee = Employee::factory()->create();
        $contact = EmployeeEmergencyContact::factory()->for($employee)->create([
            'mobile' => '03001234567',
            'address' => 'Private emergency address',
        ]);
        $bankAccount = EmployeeBankAccount::factory()->for($employee)->create([
            'account_number' => '999988887777',
            'iban' => 'PK99PRIVATE123456789',
        ]);

        $serializedProperties = DB::table('activity_log')
            ->where(function ($query) use ($contact, $bankAccount): void {
                $query->where(function ($contactQuery) use ($contact): void {
                    $contactQuery
                        ->where('subject_type', EmployeeEmergencyContact::class)
                        ->where('subject_id', $contact->getKey());
                })->orWhere(function ($bankQuery) use ($bankAccount): void {
                    $bankQuery
                        ->where('subject_type', EmployeeBankAccount::class)
                        ->where('subject_id', $bankAccount->getKey());
                });
            })
            ->pluck('properties')
            ->implode(' ');

        $this->assertStringNotContainsString('03001234567', $serializedProperties);
        $this->assertStringNotContainsString('Private emergency address', $serializedProperties);
        $this->assertStringNotContainsString('999988887777', $serializedProperties);
        $this->assertStringNotContainsString('PK99PRIVATE123456789', $serializedProperties);
    }
}
