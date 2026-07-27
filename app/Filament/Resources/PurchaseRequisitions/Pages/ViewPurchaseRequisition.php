<?php

namespace App\Filament\Resources\PurchaseRequisitions\Pages;

use App\Filament\Resources\PurchaseRequisitions\PurchaseRequisitionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseRequisition extends ViewRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
