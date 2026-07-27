<?php

namespace Database\Seeders;

use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Models\Company;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProvisionDefaultDocumentCategoriesAction $provisionCategories): void
    {
        Company::query()
            ->active()
            ->each(
                fn (Company $company) => $provisionCategories->handle($company),
            );
    }
}
