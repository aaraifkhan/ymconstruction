<?php

namespace App\Filament\Resources\VendorBills\Actions;

use App\Actions\AccountsPayable\ApproveVendorBillAction;
use App\Actions\AccountsPayable\PostVendorBillAction;
use App\Actions\AccountsPayable\RejectVendorBillAction;
use App\Actions\AccountsPayable\ReverseVendorBillAction;
use App\Actions\AccountsPayable\ReviewVendorBillMatchAction;
use App\Actions\AccountsPayable\SubmitVendorBillAction;
use App\Enums\VendorBillStatus;
use App\Models\VendorBill;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class VendorBillWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (VendorBill $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (VendorBill $record) => app(SubmitVendorBillAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function review(): Action
    {
        return Action::make('reviewMatch')->authorize('reviewMatch')->label('Review match')
            ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Submitted)
            ->schema([
                Toggle::make('override_mismatch')
                    ->label('Authorize mismatch exception')
                    ->helperText('Requires the dedicated Override Match permission.'),
                Textarea::make('mismatch_reason'),
            ])->action(fn (VendorBill $record, array $data) => app(ReviewVendorBillMatchAction::class)
            ->handle(
                $record,
                Filament::auth()->user(),
                (bool) ($data['override_mismatch'] ?? false),
                $data['mismatch_reason'] ?? null,
            ));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Reviewed)
            ->requiresConfirmation()
            ->action(fn (VendorBill $record) => app(ApproveVendorBillAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (VendorBill $record): bool => in_array(
                $record->status,
                [VendorBillStatus::Submitted, VendorBillStatus::Reviewed],
                true,
            ))->schema([Textarea::make('reason')->required()])
            ->action(fn (VendorBill $record, array $data) => app(RejectVendorBillAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('success')
            ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (VendorBill $record) => app(PostVendorBillAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->authorize('reverse')->color('danger')
            ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required(),
            ])->action(fn (VendorBill $record, array $data) => app(ReverseVendorBillAction::class)
            ->handle($record, Filament::auth()->user(), Carbon::parse($data['reversal_date']), $data['reason']));
    }
}
