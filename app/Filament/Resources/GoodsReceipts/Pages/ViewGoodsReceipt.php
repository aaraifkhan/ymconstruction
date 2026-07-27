<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Filament\Resources\GoodsReceipts\Actions\GoodsReceiptWorkflowActions;
use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceipt extends ViewRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (GoodsReceipt $record): bool => $record->isEditable()),
            GoodsReceiptWorkflowActions::receive(),
            GoodsReceiptWorkflowActions::inspect(),
            GoodsReceiptWorkflowActions::handover(),
            GoodsReceiptWorkflowActions::returnRejected(),
        ];
    }
}
