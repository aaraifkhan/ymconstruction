<?php

namespace App\Filament\Resources\InventoryTransactions\Tables;

use App\Filament\Resources\InventoryTransactions\Actions\InventoryTransactionWorkflowActions;
use App\Models\InventoryTransaction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transaction_number')->searchable()->placeholder('Draft'),
            TextColumn::make('type')->badge(),
            TextColumn::make('transaction_date')->date()->sortable(),
            TextColumn::make('sourceSite.name')->label('Source')->placeholder('-'),
            TextColumn::make('destinationSite.name')->label('Destination')->placeholder('-'),
            TextColumn::make('project.name')->placeholder('-'),
            TextColumn::make('status')->badge(),
            TextColumn::make('total_value')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('preparedBy.name')->label('Prepared by'),
            TextColumn::make('postedBy.name')->label('Posted by')->placeholder('-'),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (InventoryTransaction $record): bool => $record->isEditable()),
                InventoryTransactionWorkflowActions::post(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
