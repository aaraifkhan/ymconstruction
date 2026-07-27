<?php

namespace App\Filament\Resources\FinancialPeriods\Tables;

use App\Filament\Resources\FinancialPeriods\Actions\FinancialPeriodWorkflowActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinancialPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('financialYear.name')->label('Financial year'),
                TextColumn::make('period_number')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('starts_on')->date()->sortable(),
                TextColumn::make('ends_on')->date(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                FinancialPeriodWorkflowActions::close(),
                FinancialPeriodWorkflowActions::lock(),
                FinancialPeriodWorkflowActions::reopen(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
