<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Enums\GoodsReceiptStatus;
use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'status' => GoodsReceiptStatus::Draft,
        ];
    }
}
