<?php

namespace App\Filament\Resources\YearEndClosings\Pages;

use App\Filament\Resources\YearEndClosings\YearEndClosingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewYearEndClosing extends ViewRecord
{
    protected static string $resource = YearEndClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
