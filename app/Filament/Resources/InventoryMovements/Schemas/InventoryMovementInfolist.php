<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Immutable stock-ledger movement')->columns(3)->schema([
                TextEntry::make('occurred_at')->dateTime(),
                TextEntry::make('movement_type')->badge(),
                TextEntry::make('direction')->badge(),
                TextEntry::make('projectSite.name')->label('Site / store'),
                TextEntry::make('counterpartySite.name')->label('Other site')->placeholder('-'),
                TextEntry::make('project.name')->placeholder('-'),
                TextEntry::make('item.code')->label('Item code'),
                TextEntry::make('item.name')->label('Item'),
                TextEntry::make('quantity')->numeric(decimalPlaces: 4),
                TextEntry::make('unit_cost')->numeric(decimalPlaces: 4),
                TextEntry::make('movement_value')->numeric(decimalPlaces: 4),
                TextEntry::make('quantity_after')->numeric(decimalPlaces: 4),
                TextEntry::make('inventory_value_after')->numeric(decimalPlaces: 4),
                TextEntry::make('average_unit_cost_after')->numeric(decimalPlaces: 4),
                TextEntry::make('goodsReceipt.goods_receipt_number')->label('GRN source')->placeholder('-'),
                TextEntry::make('inventoryTransaction.transaction_number')->label('Inventory source')->placeholder('-'),
                TextEntry::make('actor.name'),
            ]),
        ]);
    }
}
