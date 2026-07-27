<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GoodsReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Goods Receipt')->columns(3)->schema([
                TextEntry::make('goods_receipt_number')->placeholder('Draft'),
                TextEntry::make('purchaseOrder.purchase_order_number')->label('Purchase order'),
                TextEntry::make('vendor.name'),
                TextEntry::make('project.name'),
                TextEntry::make('projectSite.name')->label('Site / store'),
                TextEntry::make('delivery_date')->date(),
                TextEntry::make('delivery_reference')->placeholder('-'),
                TextEntry::make('status')->badge(),
                TextEntry::make('accepted_value')->numeric(decimalPlaces: 4),
                TextEntry::make('receivedBy.name')->label('Received by')->placeholder('-'),
                TextEntry::make('received_at')->dateTime()->placeholder('-'),
                TextEntry::make('inspectedBy.name')->label('Inspected by')->placeholder('-'),
                TextEntry::make('inspected_at')->dateTime()->placeholder('-'),
                TextEntry::make('handedOverBy.name')->label('Handed over by')->placeholder('-'),
                TextEntry::make('handed_over_at')->dateTime()->placeholder('-'),
                TextEntry::make('inventoryJournalEntry.voucher_number')->label('Inventory journal')->placeholder('-'),
                TextEntry::make('receiving_notes')->columnSpanFull()->placeholder('-'),
                TextEntry::make('inspection_notes')->columnSpanFull()->placeholder('-'),
            ]),
            Section::make('Inspection lines')->schema([
                RepeatableEntry::make('lines')->schema([
                    TextEntry::make('line_number')->label('#'),
                    TextEntry::make('item_name_snapshot')->label('Item'),
                    TextEntry::make('uom_snapshot')->label('UOM'),
                    TextEntry::make('received_quantity')->numeric(decimalPlaces: 4),
                    TextEntry::make('accepted_quantity')->numeric(decimalPlaces: 4),
                    TextEntry::make('rejected_quantity')->numeric(decimalPlaces: 4),
                    TextEntry::make('rejected_returned_quantity')->label('Rejected returned')->numeric(decimalPlaces: 4),
                    TextEntry::make('accepted_returned_quantity')->label('Accepted returned')->numeric(decimalPlaces: 4),
                    TextEntry::make('unit_cost_snapshot')->label('Unit cost')->numeric(decimalPlaces: 4),
                    TextEntry::make('accepted_value')->numeric(decimalPlaces: 4),
                    TextEntry::make('inspection_result')->badge()->placeholder('Pending'),
                    TextEntry::make('rejection_reason')->placeholder('-'),
                ])->columns(4),
            ]),
        ]);
    }
}
