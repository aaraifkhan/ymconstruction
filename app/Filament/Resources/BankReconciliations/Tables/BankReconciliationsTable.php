<?php

namespace App\Filament\Resources\BankReconciliations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankReconciliationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('companyBankAccount.bank_name')->label('Bank')->searchable(),
            TextColumn::make('period_start')->date()->sortable(),
            TextColumn::make('period_end')->date()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('statement_closing_balance')->numeric(decimalPlaces: 4),
            TextColumn::make('book_closing_balance')->numeric(decimalPlaces: 4),
            TextColumn::make('difference')->numeric(decimalPlaces: 4),
            TextColumn::make('matches_count')->counts('matches')->label('Matches'),
        ])->defaultSort('period_end', 'desc')->recordActions([ViewAction::make()]);
    }
}
