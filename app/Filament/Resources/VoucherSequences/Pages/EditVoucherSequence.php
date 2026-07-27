<?php

namespace App\Filament\Resources\VoucherSequences\Pages;

use App\Filament\Resources\VoucherSequences\VoucherSequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVoucherSequence extends EditRecord
{
    protected static string $resource = VoucherSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
