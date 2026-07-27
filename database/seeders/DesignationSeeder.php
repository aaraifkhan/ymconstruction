<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()->each(
            fn (Company $company) => Designation::factory()->count(4)->for($company)->create(),
        );
    }
}
