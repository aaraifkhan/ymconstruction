<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('occurred_at')->dateTime()->sortable(),
            TextColumn::make('movement_type')->badge(),
            TextColumn::make('direction')->badge(),
            TextColumn::make('projectSite.name')->label('Site / store')->searchable(),
            TextColumn::make('item.code')->label('Item code')->searchable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('quantity')->numeric(decimalPlaces: 4),
            TextColumn::make('unit_cost')->numeric(decimalPlaces: 4),
            TextColumn::make('movement_value')->numeric(decimalPlaces: 4),
            TextColumn::make('quantity_after')->numeric(decimalPlaces: 4),
            TextColumn::make('actor.name'),
        ])->defaultSort('occurred_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }
}
