<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Tables;

use App\Filament\Resources\OpeningBalanceBatches\Actions\OpeningBalanceWorkflowActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpeningBalanceBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('source_name')->placeholder('Manual opening balance')->searchable(),
            TextColumn::make('opening_date')->date()->sortable(),
            TextColumn::make('financialPeriod.name')->label('Period'),
            TextColumn::make('debit_total')->numeric(decimalPlaces: 2),
            TextColumn::make('credit_total')->numeric(decimalPlaces: 2),
            TextColumn::make('status')->badge(),
            TextColumn::make('preparedBy.name')->label('Prepared by'),
            TextColumn::make('journalEntry.voucher_number')->label('OB voucher')->placeholder('Not posted'),
        ])->recordActions([
            ViewAction::make(), EditAction::make(), DeleteAction::make(),
            OpeningBalanceWorkflowActions::validate(), OpeningBalanceWorkflowActions::post(),
        ]);
    }
}
