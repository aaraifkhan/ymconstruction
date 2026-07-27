<?php

namespace App\Filament\Resources\VoucherSequences\Pages;

use App\Filament\Resources\VoucherSequences\VoucherSequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVoucherSequences extends ListRecords
{
    protected static string $resource = VoucherSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
