<?php

namespace App\Filament\Resources\VendorBills\Tables;

use App\Filament\Resources\VendorBills\Actions\VendorBillWorkflowActions;
use App\Models\VendorBill;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorBillsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('vendor_bill_number')->searchable()->placeholder('Draft'),
            TextColumn::make('vendor_invoice_number')->searchable(),
            TextColumn::make('vendor.name')->searchable(),
            TextColumn::make('purchaseOrder.purchase_order_number')->label('PO')->searchable()->placeholder('-'),
            TextColumn::make('type')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('match_status')->badge()->placeholder('-'),
            TextColumn::make('invoice_date')->date()->sortable(),
            TextColumn::make('due_date')->date()->sortable(),
            TextColumn::make('net_payable')->numeric(decimalPlaces: 4)->sortable(),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (VendorBill $record): bool => $record->isEditable()),
                VendorBillWorkflowActions::submit(),
                VendorBillWorkflowActions::review(),
                VendorBillWorkflowActions::approve(),
                VendorBillWorkflowActions::reject(),
                VendorBillWorkflowActions::post(),
                VendorBillWorkflowActions::reverse(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
