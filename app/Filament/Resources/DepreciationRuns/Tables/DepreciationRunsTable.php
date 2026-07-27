<?php

namespace App\Filament\Resources\DepreciationRuns\Tables;

use App\Enums\AssetAccountingStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepreciationRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->placeholder('Pending')->searchable(),
                TextColumn::make('financialPeriod.period_number')->label('Period'),
                TextColumn::make('depreciation_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('lines_count')->counts('lines')->label('Assets'),
                TextColumn::make('total_amount')->money('PKR')->sortable(),
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
            ])->defaultSort('depreciation_date', 'desc');
    }
}
