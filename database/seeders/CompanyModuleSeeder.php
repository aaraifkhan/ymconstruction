<?php

namespace Database\Seeders;

use App\Actions\Companies\ProvisionOrganizationCompaniesAction;
use Illuminate\Database\Seeder;

class CompanyModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProvisionOrganizationCompaniesAction $provisionOrganization): void
    {
        $this->call(ModuleSeeder::class);

        $provisionOrganization->handle();
    }
}
