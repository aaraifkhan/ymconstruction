<?php

namespace App\Filament\Resources\AccountingSettings\Pages;

use App\Filament\Resources\AccountingSettings\AccountingSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountingSetting extends EditRecord
{
    protected static string $resource = AccountingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
