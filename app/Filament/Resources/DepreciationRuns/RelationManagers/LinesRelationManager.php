<?php

namespace App\Filament\Resources\DepreciationRuns\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('fixedAsset.asset_number')->label('Asset number'),
                TextEntry::make('fixedAsset.name')
                    ->label('Fixed asset'),
                TextEntry::make('opening_accumulated_depreciation')
                    ->money('PKR'),
                TextEntry::make('depreciation_amount')
                    ->money('PKR'),
                TextEntry::make('closing_accumulated_depreciation')
                    ->money('PKR'),
                TextEntry::make('closing_carrying_amount')
                    ->money('PKR'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fixed_asset_id')
            ->columns([
                TextColumn::make('fixedAsset.asset_number')->label('Asset')->searchable()->sortable(),
                TextColumn::make('fixedAsset.name')->searchable(),
                TextColumn::make('opening_accumulated_depreciation')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('depreciation_amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('closing_accumulated_depreciation')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('closing_carrying_amount')
                    ->money('PKR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
