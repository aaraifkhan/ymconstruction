<?php

namespace App\Filament\Resources\EmployeeFinancings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    public function table(Table $table): Table
    {
        return $table->defaultSort('due_date')->columns([
            TextColumn::make('schedule_version')->label('Version'),
            TextColumn::make('installment_number')->label('#'),
            TextColumn::make('due_date')->date(),
            TextColumn::make('principal_due')->money('PKR'),
            TextColumn::make('finance_charge_due')->money('PKR'),
            TextColumn::make('total_due')->money('PKR'),
            TextColumn::make('recovered')->state(fn ($record): string => bcadd((string) $record->principal_recovered, (string) $record->finance_charge_recovered, 4))->money('PKR'),
            TextColumn::make('waived')->state(fn ($record): string => bcadd((string) $record->principal_waived, (string) $record->finance_charge_waived, 4))->money('PKR'),
            TextColumn::make('status')->badge(),
        ]);
    }
}
