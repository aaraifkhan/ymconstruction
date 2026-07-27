<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Pages;

use App\Filament\Resources\OpeningBalanceBatches\OpeningBalanceBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpeningBalanceBatches extends ListRecords
{
    protected static string $resource = OpeningBalanceBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
