<?php

namespace App\Filament\Resources\PayrollRuns\Tables;

use App\Enums\PayrollRunStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PayrollRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable()->sortable(),
                TextColumn::make('period_start')->date()->sortable(),
                TextColumn::make('period_end')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('calculationRule.name')->label('Calculation rule')->placeholder('Legacy')->toggleable(),
                TextColumn::make('generation_revision')->label('Revision')->sortable()->toggleable(),
                TextColumn::make('entries_count')->counts('entries')->label('Employees'),
            ])
            ->filters([
                SelectFilter::make('status')->options(PayrollRunStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('period_start', 'desc')
            ->toolbarActions([]);
    }
}
