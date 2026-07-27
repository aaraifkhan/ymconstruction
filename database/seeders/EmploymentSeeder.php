<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use Illuminate\Database\Seeder;

class EmploymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::query()->with(['departments', 'designations'])->get();

        if ($companies->isEmpty()) {
            return;
        }

        Employee::query()->each(function (Employee $employee) use ($companies): void {
            $company = $companies->random();

            Employment::factory()
                ->for($employee)
                ->forCompany($company)
                ->create([
                    'employee_code' => 'EMP-'.str_pad((string) $employee->getKey(), 5, '0', STR_PAD_LEFT),
                    'department_id' => $company->departments->shuffle()->first()?->getKey(),
                    'designation_id' => $company->designations->shuffle()->first()?->getKey(),
                ]);
        });
    }
}
