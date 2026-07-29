<?php

namespace Database\Seeders;

use App\Actions\Companies\ProvisionOrganizationCompaniesAction;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProvisionOrganizationCompaniesAction $provisionOrganization): void
    {
        $this->call(ModuleSeeder::class);

        $provisionOrganization->handle();

        $this->call([
            DocumentCategorySeeder::class,
            HrDocumentTypeSeeder::class,
            JoiningLetterTemplateSeeder::class,
        ]);
    }
}
