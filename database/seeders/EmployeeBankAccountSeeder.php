<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Database\Seeder;

class EmployeeBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::query()->each(
            fn (Employee $employee) => EmployeeBankAccount::factory()->payrollDefault()->for($employee)->create(),
        );
    }
}
