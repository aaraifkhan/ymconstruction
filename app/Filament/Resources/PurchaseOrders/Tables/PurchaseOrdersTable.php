<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Filament\Resources\PurchaseOrders\Actions\PurchaseOrderWorkflowActions;
use App\Models\PurchaseOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('requisition.requisition_number')
                    ->label('Requisition')
                    ->searchable(),
                TextColumn::make('vendor.name')
                    ->searchable(),
                TextColumn::make('project.name')
                    ->searchable(),
                TextColumn::make('projectSite.name')
                    ->searchable(),
                TextColumn::make('purchase_order_number')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('approval_round')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->searchable(),
                TextColumn::make('payment_terms_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('approved_snapshot_hash')
                    ->label('Snapshot')
                    ->limit(12)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('preparedBy.name')
                    ->searchable(),
                TextColumn::make('submittedBy.name')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->searchable(),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('rejectedBy.name')
                    ->searchable(),
                TextColumn::make('rejected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('orderedBy.name')
                    ->searchable(),
                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelledBy.name')
                    ->searchable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PurchaseOrder $record): bool => $record->isEditable()),
                PurchaseOrderWorkflowActions::submit(),
                PurchaseOrderWorkflowActions::approve(),
                PurchaseOrderWorkflowActions::reject(),
                PurchaseOrderWorkflowActions::issue(),
                PurchaseOrderWorkflowActions::cancel(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
