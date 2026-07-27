<?php

namespace App\Filament\Resources\InventoryBalances\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('projectSite.project.name')->label('Project')->searchable(),
            TextColumn::make('projectSite.name')->label('Site / store')->searchable(),
            TextColumn::make('item.code')->label('Item code')->searchable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('quantity_on_hand')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('average_unit_cost')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('inventory_value')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }
}
