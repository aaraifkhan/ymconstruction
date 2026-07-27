<?php

namespace App\Filament\Resources\PurchaseRequisitions\Tables;

use App\Filament\Resources\PurchaseRequisitions\Actions\PurchaseRequisitionWorkflowActions;
use App\Models\PurchaseRequisition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequisitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('project.name')
                    ->searchable(),
                TextColumn::make('projectSite.name')
                    ->searchable(),
                TextColumn::make('requisition_number')
                    ->searchable(),
                TextColumn::make('required_date')
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
                TextColumn::make('estimated_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('budget_check_status')
                    ->badge(),
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
                EditAction::make()->visible(fn (PurchaseRequisition $record): bool => $record->isEditable()),
                PurchaseRequisitionWorkflowActions::submit(),
                PurchaseRequisitionWorkflowActions::approve(),
                PurchaseRequisitionWorkflowActions::reject(),
                PurchaseRequisitionWorkflowActions::cancel(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
