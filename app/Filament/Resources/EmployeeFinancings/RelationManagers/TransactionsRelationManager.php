<?php

namespace App\Filament\Resources\EmployeeFinancings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('effective_date')->date(),
            TextColumn::make('type')->badge(),
            TextColumn::make('principal_amount')->money('PKR'),
            TextColumn::make('finance_charge_amount')->money('PKR'),
            TextColumn::make('total_amount')->money('PKR'),
            TextColumn::make('treasuryTransaction.transaction_number')->label('Treasury')->placeholder('-'),
            TextColumn::make('payrollEntry.payrollRun.reference_number')->label('Payroll')->placeholder('-'),
            TextColumn::make('createdBy.name')->label('Actor')->placeholder('System'),
            TextColumn::make('reason')->limit(40)->placeholder('-'),
        ]);
    }
}
