<?php

namespace App\Filament\Resources\GoodsReceipts\Tables;

use App\Filament\Resources\GoodsReceipts\Actions\GoodsReceiptWorkflowActions;
use App\Models\GoodsReceipt;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('goods_receipt_number')->searchable()->placeholder('Draft'),
            TextColumn::make('purchaseOrder.purchase_order_number')->label('PO')->searchable(),
            TextColumn::make('vendor.name')->searchable(),
            TextColumn::make('projectSite.name')->label('Site / store')->searchable(),
            TextColumn::make('delivery_date')->date()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('accepted_value')->numeric(decimalPlaces: 4)->sortable(),
            TextColumn::make('receivedBy.name')->label('Receiver')->placeholder('-'),
            TextColumn::make('inspectedBy.name')->label('Inspector')->placeholder('-'),
            TextColumn::make('handedOverBy.name')->label('Accounts handover')->placeholder('-'),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (GoodsReceipt $record): bool => $record->isEditable()),
                GoodsReceiptWorkflowActions::receive(),
                GoodsReceiptWorkflowActions::inspect(),
                GoodsReceiptWorkflowActions::handover(),
                GoodsReceiptWorkflowActions::returnRejected(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
