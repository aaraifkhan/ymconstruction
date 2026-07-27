<?php

namespace App\Filament\Resources\CustomerInvoices\Actions;

use App\Actions\AccountsReceivable\ApproveCustomerInvoiceAction;
use App\Actions\AccountsReceivable\PostCustomerInvoiceAction;
use App\Actions\AccountsReceivable\RejectCustomerInvoiceAction;
use App\Actions\AccountsReceivable\ReverseCustomerInvoiceAction;
use App\Actions\AccountsReceivable\SubmitCustomerInvoiceAction;
use App\Enums\CustomerInvoiceStatus;
use App\Models\CustomerInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class CustomerInvoiceWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (CustomerInvoice $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (CustomerInvoice $record) => app(SubmitCustomerInvoiceAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (CustomerInvoice $record): bool => $record->status === CustomerInvoiceStatus::Submitted)
            ->requiresConfirmation()
            ->action(fn (CustomerInvoice $record) => app(ApproveCustomerInvoiceAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (CustomerInvoice $record): bool => $record->status === CustomerInvoiceStatus::Submitted)
            ->schema([Textarea::make('reason')->required()])
            ->action(fn (CustomerInvoice $record, array $data) => app(RejectCustomerInvoiceAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('success')
            ->visible(fn (CustomerInvoice $record): bool => $record->status === CustomerInvoiceStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (CustomerInvoice $record) => app(PostCustomerInvoiceAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->authorize('reverse')->color('danger')
            ->visible(fn (CustomerInvoice $record): bool => $record->status === CustomerInvoiceStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required(),
            ])->action(fn (CustomerInvoice $record, array $data) => app(ReverseCustomerInvoiceAction::class)
            ->handle($record, Filament::auth()->user(), Carbon::parse($data['reversal_date']), $data['reason']));
    }
}
