<?php

namespace App\Filament\Resources\CompanyModules\Pages;

use App\Filament\Resources\CompanyModules\CompanyModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyModule extends EditRecord
{
    protected static string $resource = CompanyModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
