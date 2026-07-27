<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Database\Seeder;

class EmployeeEmergencyContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::query()->each(
            fn (Employee $employee) => EmployeeEmergencyContact::factory()->primary()->for($employee)->create(),
        );
    }
}
