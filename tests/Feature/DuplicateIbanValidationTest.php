<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DuplicateIbanValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_iban_hash_is_written_on_create(): void
    {
        $account = EmployeeBankAccount::factory()->create([
            'iban' => 'PK00TEST0000000000000001',
        ]);

        $raw = DB::table((new EmployeeBankAccount)->getTable())
            ->where('id', $account->getKey())
            ->value('iban_hash');

        $this->assertNotNull($raw);
        $this->assertSame(64, strlen($raw)); // SHA-256 hex = 64 chars
    }

    public function test_iban_hash_changes_when_iban_is_updated(): void
    {
        $account = EmployeeBankAccount::factory()->create([
            'iban' => 'PK00TEST0000000000000001',
        ]);
        $firstHash = DB::table((new EmployeeBankAccount)->getTable())
            ->where('id', $account->getKey())
            ->value('iban_hash');

        $account->update(['iban' => 'PK00TEST0000000000000002']);

        $secondHash = DB::table((new EmployeeBankAccount)->getTable())
            ->where('id', $account->getKey())
            ->value('iban_hash');

        $this->assertNotSame($firstHash, $secondHash);
    }

    public function test_duplicate_iban_across_employees_raises_unique_constraint_violation(): void
    {
        $employeeOne = Employee::factory()->create();
        $employeeTwo = Employee::factory()->create();

        EmployeeBankAccount::factory()->for($employeeOne)->create([
            'iban' => 'PK00TEST0000000000000099',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        EmployeeBankAccount::factory()->for($employeeTwo)->create([
            'iban' => 'PK00TEST0000000000000099',
        ]);
    }

    public function test_same_employee_updating_iban_to_same_value_does_not_raise_exception(): void
    {
        $employee = Employee::factory()->create();
        $account = EmployeeBankAccount::factory()->for($employee)->create([
            'iban' => 'PK00TEST0000000000000077',
        ]);

        // Updating the same IBAN on the same record must not fail.
        $account->update(['iban' => 'PK00TEST0000000000000077']);

        $this->assertSame('PK00TEST0000000000000077', $account->fresh()->iban);
    }

    public function test_null_iban_does_not_produce_hash(): void
    {
        $account = EmployeeBankAccount::factory()->create(['iban' => null]);

        $raw = DB::table((new EmployeeBankAccount)->getTable())
            ->where('id', $account->getKey())
            ->value('iban_hash');

        $this->assertNull($raw);
    }
}
