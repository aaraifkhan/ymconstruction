<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()->each(
            fn (Company $company) => Department::factory()->count(3)->for($company)->create(),
        );
    }
}
