<?php

namespace Database\Seeders;

use App\Models\Employment;
use App\Models\EmploymentCompensation;
use Illuminate\Database\Seeder;

class EmploymentCompensationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employment::query()
            ->whereDoesntHave('compensations')
            ->each(function (Employment $employment): void {
                EmploymentCompensation::factory()->create([
                    'company_id' => $employment->company_id,
                    'employment_id' => $employment->getKey(),
                    'effective_from' => $employment->joining_date,
                ]);
            });
    }
}
