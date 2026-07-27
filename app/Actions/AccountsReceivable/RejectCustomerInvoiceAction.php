<?php

namespace App\Actions\AccountsReceivable;

use App\Enums\CustomerInvoiceStatus;
use App\Models\CustomerInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectCustomerInvoiceAction
{
    public function handle(CustomerInvoice $invoice, User $actor, string $reason): CustomerInvoice
    {
        Gate::forUser($actor)->authorize('reject', $invoice);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($actor, $invoice, $reason): CustomerInvoice {
            $invoice = CustomerInvoice::query()->whereKey($invoice)->lockForUpdate()->firstOrFail();
            if (! in_array($invoice->status, [CustomerInvoiceStatus::Submitted, CustomerInvoiceStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Only a submitted or approved Customer Invoice may be rejected.']);
            }
            $invoice->update([
                'status' => CustomerInvoiceStatus::Rejected, 'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(), 'rejection_reason' => $reason,
            ]);
            activity('customer_invoices')->causedBy($actor)->performedOn($invoice)->event('rejected')
                ->withProperties(['company_id' => $invoice->company_id, 'reason' => $reason])
                ->log('rejected Customer Invoice');

            return $invoice->refresh();
        }, attempts: 3);
    }
}
