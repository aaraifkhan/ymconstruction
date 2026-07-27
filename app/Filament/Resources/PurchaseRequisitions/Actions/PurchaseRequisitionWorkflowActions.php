<?php

namespace App\Filament\Resources\PurchaseRequisitions\Actions;

use App\Actions\Procurement\ApproveProcurementDocumentAction;
use App\Actions\Procurement\CancelPurchaseRequisitionAction;
use App\Actions\Procurement\RejectProcurementDocumentAction;
use App\Actions\Procurement\SubmitPurchaseRequisitionAction;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseRequisition;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;

class PurchaseRequisitionWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (PurchaseRequisition $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (PurchaseRequisition $record) => app(SubmitPurchaseRequisitionAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (PurchaseRequisition $record): bool => $record->status === PurchaseRequisitionStatus::Submitted)
            ->requiresConfirmation()
            ->action(fn (PurchaseRequisition $record) => app(ApproveProcurementDocumentAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (PurchaseRequisition $record): bool => $record->status === PurchaseRequisitionStatus::Submitted)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (PurchaseRequisition $record, array $data) => app(RejectProcurementDocumentAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')->authorize('cancel')->color('danger')
            ->visible(fn (PurchaseRequisition $record): bool => in_array($record->status, [
                PurchaseRequisitionStatus::Draft,
                PurchaseRequisitionStatus::Rejected,
                PurchaseRequisitionStatus::Approved,
            ], true))
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (PurchaseRequisition $record, array $data) => app(CancelPurchaseRequisitionAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }
}
