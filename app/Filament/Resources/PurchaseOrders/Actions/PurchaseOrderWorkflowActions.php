<?php

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Actions\Procurement\ApproveProcurementDocumentAction;
use App\Actions\Procurement\CancelPurchaseOrderAction;
use App\Actions\Procurement\IssuePurchaseOrderAction;
use App\Actions\Procurement\RejectProcurementDocumentAction;
use App\Actions\Procurement\SubmitPurchaseOrderAction;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;

class PurchaseOrderWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (PurchaseOrder $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (PurchaseOrder $record) => app(SubmitPurchaseOrderAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrderStatus::Submitted)
            ->requiresConfirmation()
            ->action(fn (PurchaseOrder $record) => app(ApproveProcurementDocumentAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrderStatus::Submitted)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (PurchaseOrder $record, array $data) => app(RejectProcurementDocumentAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function issue(): Action
    {
        return Action::make('issue')->authorize('issue')->color('primary')
            ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrderStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (PurchaseOrder $record) => app(IssuePurchaseOrderAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')->authorize('cancel')->color('danger')
            ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::Rejected,
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Ordered,
            ], true))
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (PurchaseOrder $record, array $data) => app(CancelPurchaseOrderAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }
}
