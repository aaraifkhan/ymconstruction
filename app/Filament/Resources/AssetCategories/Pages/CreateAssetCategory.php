<?php

namespace App\Filament\Resources\AssetCategories\Pages;

use App\Filament\Resources\AssetCategories\AssetCategoryResource;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

class CreateAssetCategory extends CreateRecord
{
    protected static string $resource = AssetCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new LogicException('A company tenant is required.');
        }

        return [...$data, 'company_id' => $company->getKey()];
    }
}
