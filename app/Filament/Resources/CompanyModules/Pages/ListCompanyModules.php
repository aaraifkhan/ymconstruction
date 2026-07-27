<?php

namespace App\Filament\Resources\CompanyModules\Pages;

use App\Filament\Resources\CompanyModules\CompanyModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyModules extends ListRecords
{
    protected static string $resource = CompanyModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
