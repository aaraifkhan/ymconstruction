<?php

namespace App\Filament\Resources\BankStatements\Tables;

use App\Enums\BankStatementStatus;
use App\Models\BankStatement;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankStatementsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('companyBankAccount.bank_name')->label('Bank')->searchable(),
            TextColumn::make('companyBankAccount.account_title')->label('Account title')->searchable(),
            TextColumn::make('period_start')->date()->sortable(),
            TextColumn::make('period_end')->date()->sortable(),
            TextColumn::make('opening_balance')->numeric(decimalPlaces: 4),
            TextColumn::make('closing_balance')->numeric(decimalPlaces: 4),
            TextColumn::make('status')->badge(),
            TextColumn::make('lines_count')->counts('lines')->label('Lines'),
        ])->defaultSort('period_end', 'desc')->recordActions([
            ViewAction::make(),
            EditAction::make()->visible(fn (BankStatement $record): bool => $record->status === BankStatementStatus::Draft),
        ])->toolbarActions([DeleteBulkAction::make()]);
    }
}
