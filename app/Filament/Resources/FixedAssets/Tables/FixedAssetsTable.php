<?php

namespace App\Filament\Resources\FixedAssets\Tables;

use App\Enums\AssetStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FixedAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_number')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('location')->searchable()->placeholder('—'),
                TextColumn::make('acquisition_cost')->money('PKR')->sortable(),
                TextColumn::make('accumulated_depreciation')->money('PKR'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->options(AssetStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])->defaultSort('asset_number');
    }
}
