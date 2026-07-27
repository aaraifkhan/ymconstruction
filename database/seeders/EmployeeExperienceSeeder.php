<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use Illuminate\Database\Seeder;

class EmployeeExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::query()->each(
            fn (Employee $employee) => EmployeeExperience::factory()->for($employee)->create(),
        );
    }
}
