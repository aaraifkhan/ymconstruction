<?php

namespace App\Filament\Resources\AccountingSettings\Pages;

use App\Filament\Resources\AccountingSettings\AccountingSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountingSetting extends CreateRecord
{
    protected static string $resource = AccountingSettingResource::class;
}
