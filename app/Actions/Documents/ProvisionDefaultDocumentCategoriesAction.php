<?php

namespace App\Actions\Documents;

use App\Enums\DocumentClassification;
use App\Models\Company;
use App\Models\DocumentCategory;
use Illuminate\Support\Str;

class ProvisionDefaultDocumentCategoriesAction
{
    /**
     * @return array<int, DocumentCategory>
     */
    public function handle(Company $company): array
    {
        $categories = [
            ['name' => 'Company Registration', 'classification' => DocumentClassification::Confidential],
            ['name' => 'Employee Document', 'classification' => DocumentClassification::Restricted],
            ['name' => 'Contract', 'classification' => DocumentClassification::Confidential],
            ['name' => 'Financial Document', 'classification' => DocumentClassification::Restricted],
            ['name' => 'General Document', 'classification' => DocumentClassification::Internal],
        ];

        return collect($categories)
            ->map(
                fn (array $category): DocumentCategory => DocumentCategory::query()->firstOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'slug' => Str::slug($category['name']),
                    ],
                    [
                        'name' => $category['name'],
                        'default_classification' => $category['classification'],
                        'is_active' => true,
                    ],
                ),
            )
            ->all();
    }
}
