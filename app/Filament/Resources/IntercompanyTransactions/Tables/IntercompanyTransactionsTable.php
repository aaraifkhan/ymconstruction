<?php

namespace App\Filament\Resources\IntercompanyTransactions\Tables;

use App\Enums\IntercompanyStatus;
use App\Filament\Resources\IntercompanyTransactions\Actions\IntercompanyWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IntercompanyTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transaction_date')->date()->sortable(),
            TextColumn::make('company.name')->label('Origin')->searchable(),
            TextColumn::make('counterpartyCompany.name')->label('Counterparty')->searchable(),
            TextColumn::make('direction')->badge(),
            TextColumn::make('amount')->money('PKR')->sortable(),
            TextColumn::make('reference')->placeholder('—')->searchable(),
            TextColumn::make('status')->badge()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(IntercompanyStatus::class),
        ])->recordActions([
            IntercompanyWorkflowActions::submit(),
            IntercompanyWorkflowActions::approveOrigin(),
            IntercompanyWorkflowActions::approveCounterparty(),
            IntercompanyWorkflowActions::reject(),
            IntercompanyWorkflowActions::post(),
            IntercompanyWorkflowActions::reverse(),
            ViewAction::make(),
            EditAction::make(),
        ])->defaultSort('transaction_date', 'desc');
    }
}
