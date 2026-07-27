<?php

namespace App\Filament\Resources\CustomerInvoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer invoice')->columns(4)->schema([
                TextEntry::make('invoice_number')->placeholder('Draft'),
                TextEntry::make('customer.name'),
                TextEntry::make('project.name')->placeholder('-'),
                TextEntry::make('type')->badge(),
                TextEntry::make('category')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('invoice_date')->date(),
                TextEntry::make('due_date')->date(),
                TextEntry::make('certificate_number')->placeholder('-'),
                TextEntry::make('subtotal')->money('PKR', divideBy: 1),
                TextEntry::make('tax_total')->money('PKR', divideBy: 1),
                TextEntry::make('gross_total')->money('PKR', divideBy: 1),
                TextEntry::make('retention_amount')->money('PKR', divideBy: 1),
                TextEntry::make('wht_amount')->money('PKR', divideBy: 1),
                TextEntry::make('mobilization_recovery_amount')->money('PKR', divideBy: 1),
                TextEntry::make('receivable_amount')->money('PKR', divideBy: 1),
                TextEntry::make('commercial_snapshot_hash')->label('Approved snapshot hash')->placeholder('-')->columnSpanFull(),
            ]),
        ]);
    }
}
