<?php

namespace App\Filament\Resources\AccountingSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountingSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profile')->badge(),
                TextColumn::make('base_currency_code')->label('Currency'),
                TextColumn::make('timezone'),
                TextColumn::make('fiscal_year_start_month')->label('FY start month'),
                TextColumn::make('inventory_valuation_method')->badge(),
                IconColumn::make('allow_negative_inventory')->boolean(),
            ])
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
