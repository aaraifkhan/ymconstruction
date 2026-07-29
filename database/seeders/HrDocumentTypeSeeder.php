<?php

namespace Database\Seeders;

use App\Actions\Documents\ProvisionDefaultHrDocumentTypesAction;
use App\Models\Company;
use Illuminate\Database\Seeder;

class HrDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProvisionDefaultHrDocumentTypesAction $provisionTypes): void
    {
        Company::query()
            ->active()
            ->each(fn (Company $company) => $provisionTypes->handle($company));
    }
}
