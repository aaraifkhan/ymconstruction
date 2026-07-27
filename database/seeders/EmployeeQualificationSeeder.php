<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeQualification;
use Illuminate\Database\Seeder;

class EmployeeQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::query()->each(
            fn (Employee $employee) => EmployeeQualification::factory()->for($employee)->create(),
        );
    }
}
