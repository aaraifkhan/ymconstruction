<?php

namespace App\Filament\Resources\VendorBills\Pages;

use App\Filament\Resources\VendorBills\Actions\VendorBillWorkflowActions;
use App\Filament\Resources\VendorBills\VendorBillResource;
use App\Models\VendorBill;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorBill extends ViewRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (VendorBill $record): bool => $record->isEditable()),
            VendorBillWorkflowActions::submit(),
            VendorBillWorkflowActions::review(),
            VendorBillWorkflowActions::approve(),
            VendorBillWorkflowActions::reject(),
            VendorBillWorkflowActions::post(),
            VendorBillWorkflowActions::reverse(),
        ];
    }
}
