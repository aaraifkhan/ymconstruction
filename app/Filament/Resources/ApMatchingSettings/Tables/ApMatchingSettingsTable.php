<?php

namespace App\Filament\Resources\ApMatchingSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApMatchingSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity_tolerance_percentage')->label('Qty tolerance')->suffix('%'),
                TextColumn::make('rate_tolerance_percentage')->label('Rate tolerance')->suffix('%'),
                TextColumn::make('tax_tolerance_percentage')->label('Tax tolerance')->suffix('%'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
