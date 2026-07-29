<?php

namespace App\Filament\Resources\HrDataMigrations\Pages;

use App\Filament\Resources\HrDataMigrations\HrDataMigrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHrDataMigration extends EditRecord
{
    protected static string $resource = HrDataMigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
