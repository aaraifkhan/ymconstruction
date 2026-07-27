<?php

namespace App\Filament\Resources\VendorBills\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorBillInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor Bill')->columns(4)->schema([
                TextEntry::make('vendor_bill_number')->placeholder('Draft'),
                TextEntry::make('vendor_invoice_number'),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('vendor.name'),
                TextEntry::make('purchaseOrder.purchase_order_number')->label('PO')->placeholder('-'),
                TextEntry::make('invoice_date')->date(),
                TextEntry::make('due_date')->date(),
                TextEntry::make('match_status')->badge()->placeholder('-'),
                TextEntry::make('subtotal')->money('PKR', divideBy: 1),
                TextEntry::make('tax_total')->money('PKR', divideBy: 1),
                TextEntry::make('deduction_total')->money('PKR', divideBy: 1),
                TextEntry::make('net_payable')->money('PKR', divideBy: 1),
                TextEntry::make('journalEntry.voucher_number')->label('Posted voucher')->placeholder('-'),
                TextEntry::make('mismatch_reason')->columnSpanFull()->placeholder('-'),
                TextEntry::make('rejection_reason')->columnSpanFull()->placeholder('-'),
            ]),
        ]);
    }
}
