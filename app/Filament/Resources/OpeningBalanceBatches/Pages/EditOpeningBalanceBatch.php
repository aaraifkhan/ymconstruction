<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Pages;

use App\Filament\Resources\OpeningBalanceBatches\OpeningBalanceBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOpeningBalanceBatch extends EditRecord
{
    protected static string $resource = OpeningBalanceBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
