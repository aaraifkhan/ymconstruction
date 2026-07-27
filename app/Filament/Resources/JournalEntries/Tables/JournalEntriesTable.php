<?php

namespace App\Filament\Resources\JournalEntries\Tables;

use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntries\Actions\JournalWorkflowActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('voucher_number')->placeholder('Pending')->searchable(),
                TextColumn::make('transaction_date')->date()->sortable(),
                TextColumn::make('voucher_type')->badge(),
                TextColumn::make('description')->limit(40)->searchable(),
                TextColumn::make('debit_total')->numeric(decimalPlaces: 2),
                TextColumn::make('credit_total')->numeric(decimalPlaces: 2),
                TextColumn::make('status')->badge()->color(fn ($state) => $state->color()),
                TextColumn::make('preparedBy.name')->label('Prepared by'),
                TextColumn::make('posted_at')->dateTime()->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(JournalStatus::class)])
            ->recordActions([
                ViewAction::make(), EditAction::make(), DeleteAction::make(),
                JournalWorkflowActions::submit(), JournalWorkflowActions::approve(), JournalWorkflowActions::reject(),
                JournalWorkflowActions::post(), JournalWorkflowActions::reverse(),
            ]);
    }
}
