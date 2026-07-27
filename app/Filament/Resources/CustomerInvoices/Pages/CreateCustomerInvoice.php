<?php

namespace App\Filament\Resources\CustomerInvoices\Pages;

use App\Enums\CustomerInvoiceStatus;
use App\Filament\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerInvoice extends CreateRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'prepared_by_id' => Filament::auth()->id(),
            'status' => CustomerInvoiceStatus::Draft,
            'currency_code' => 'PKR',
        ];
    }
}
