<?php

namespace App\Filament\Resources\CompanyModules\Pages;

use App\Filament\Resources\CompanyModules\CompanyModuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyModule extends ViewRecord
{
    protected static string $resource = CompanyModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
