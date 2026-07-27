<?php

namespace App\Filament\Resources\VendorBills\Pages;

use App\Filament\Resources\VendorBills\VendorBillResource;
use App\Models\VendorBill;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (VendorBill $record): bool => $record->isEditable()),
        ];
    }
}
