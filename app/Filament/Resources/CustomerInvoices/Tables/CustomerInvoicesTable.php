<?php

namespace App\Filament\Resources\CustomerInvoices\Tables;

use App\Filament\Resources\CustomerInvoices\Actions\CustomerInvoiceWorkflowActions;
use App\Models\CustomerInvoice;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable()->placeholder('Draft'),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('project.name')->searchable()->placeholder('-'),
                TextColumn::make('type')->badge(),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('invoice_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('gross_total')->numeric(decimalPlaces: 4)->sortable(),
                TextColumn::make('receivable_amount')->numeric(decimalPlaces: 4)->sortable(),
            ])->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (CustomerInvoice $record): bool => $record->isEditable()),
                CustomerInvoiceWorkflowActions::submit(),
                CustomerInvoiceWorkflowActions::approve(),
                CustomerInvoiceWorkflowActions::reject(),
                CustomerInvoiceWorkflowActions::post(),
                CustomerInvoiceWorkflowActions::reverse(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
