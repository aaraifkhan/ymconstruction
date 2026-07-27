<?php

namespace App\Filament\Resources\VoucherSequences\Pages;

use App\Filament\Resources\VoucherSequences\VoucherSequenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVoucherSequence extends ViewRecord
{
    protected static string $resource = VoucherSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
