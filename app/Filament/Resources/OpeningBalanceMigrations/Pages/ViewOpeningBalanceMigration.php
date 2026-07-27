<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Pages;

use App\Filament\Resources\OpeningBalanceMigrations\OpeningBalanceMigrationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOpeningBalanceMigration extends ViewRecord
{
    protected static string $resource = OpeningBalanceMigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
