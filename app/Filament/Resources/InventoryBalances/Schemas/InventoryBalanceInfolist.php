<?php

namespace App\Filament\Resources\InventoryBalances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryBalanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Current stock')->columns(2)->schema([
                TextEntry::make('projectSite.name')->label('Site / store'),
                TextEntry::make('projectSite.project.name')->label('Project'),
                TextEntry::make('item.code')->label('Item code'),
                TextEntry::make('item.name')->label('Item'),
                TextEntry::make('quantity_on_hand')->numeric(decimalPlaces: 4),
                TextEntry::make('average_unit_cost')->numeric(decimalPlaces: 4),
                TextEntry::make('inventory_value')->numeric(decimalPlaces: 4),
                TextEntry::make('updated_at')->dateTime(),
            ]),
        ]);
    }
}
