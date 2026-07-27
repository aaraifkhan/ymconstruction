<?php

namespace App\Filament\Resources\TreasuryTransactions\Tables;

use App\Filament\Resources\TreasuryTransactions\Actions\TreasuryWorkflowActions;
use App\Models\TreasuryTransaction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreasuryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transaction_number')->searchable()->placeholder('Draft'),
            TextColumn::make('type')->badge(),
            TextColumn::make('purpose')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('transaction_date')->date()->sortable(),
            TextColumn::make('party.name')->label('Party')->searchable()->placeholder('-'),
            TextColumn::make('employment.employee.full_name')->label('Employee')->placeholder('-'),
            TextColumn::make('amount')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('allocated_amount')->numeric(decimalPlaces: 4),
            TextColumn::make('bank_reference')->placeholder('-')->toggleable(),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (TreasuryTransaction $record): bool => $record->isEditable()),
                TreasuryWorkflowActions::submit(),
                TreasuryWorkflowActions::approve(),
                TreasuryWorkflowActions::reject(),
                TreasuryWorkflowActions::post(),
                TreasuryWorkflowActions::reverse(),
            ])->toolbarActions([DeleteBulkAction::make()]);
    }
}
