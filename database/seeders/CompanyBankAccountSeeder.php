<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyBankAccount;
use Illuminate\Database\Seeder;

class CompanyBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $companies = Company::factory()->count(4)->create();
        }

        $companies->each(
            fn (Company $company) => CompanyBankAccount::factory()
                ->count(2)
                ->for($company)
                ->create()
        );
    }
}
