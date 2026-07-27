<?php

namespace App\Filament\Resources\InventoryTransactions\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inventory transaction')->columns(3)->schema([
                TextEntry::make('transaction_number')->placeholder('Draft'),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('transaction_date')->date(),
                TextEntry::make('sourceSite.name')->label('Source')->placeholder('-'),
                TextEntry::make('destinationSite.name')->label('Destination')->placeholder('-'),
                TextEntry::make('project.name')->placeholder('-'),
                TextEntry::make('goodsReceipt.goods_receipt_number')->label('Goods Receipt')->placeholder('-'),
                TextEntry::make('reference')->placeholder('-'),
                TextEntry::make('preparedBy.name')->label('Prepared by'),
                TextEntry::make('postedBy.name')->label('Posted by')->placeholder('-'),
                TextEntry::make('posted_at')->dateTime()->placeholder('-'),
                TextEntry::make('journalEntry.voucher_number')->label('Journal')->placeholder('-'),
                TextEntry::make('total_value')->numeric(decimalPlaces: 4),
                TextEntry::make('reason')->columnSpanFull(),
            ]),
            Section::make('Lines')->schema([
                RepeatableEntry::make('lines')->schema([
                    TextEntry::make('line_number')->label('#'),
                    TextEntry::make('item_name_snapshot')->label('Item'),
                    TextEntry::make('uom_snapshot')->label('UOM'),
                    TextEntry::make('quantity')->numeric(decimalPlaces: 4),
                    TextEntry::make('unit_cost_snapshot')->label('Unit cost')->numeric(decimalPlaces: 4),
                    TextEntry::make('line_value')->numeric(decimalPlaces: 4),
                    TextEntry::make('offsetAccount.name')->label('Cost / adjustment account')->placeholder('-'),
                    TextEntry::make('goodsReceiptLine.goodsReceipt.goods_receipt_number')->label('GRN')->placeholder('-'),
                ])->columns(4),
            ]),
        ]);
    }
}
