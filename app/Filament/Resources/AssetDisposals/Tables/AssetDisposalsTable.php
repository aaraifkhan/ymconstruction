<?php

namespace App\Filament\Resources\AssetDisposals\Tables;

use App\Enums\AssetAccountingStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetDisposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fixedAsset.asset_number')->label('Asset')->searchable(),
                TextColumn::make('fixedAsset.name')->label('Name')->searchable(),
                TextColumn::make('disposal_date')->date()->sortable(),
                TextColumn::make('proceeds_amount')->money('PKR'),
                TextColumn::make('gain_amount')->money('PKR'),
                TextColumn::make('loss_amount')->money('PKR'),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(AssetAccountingStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('disposal_date', 'desc');
    }
}
