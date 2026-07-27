<?php

namespace App\Actions\AccountsReceivable;

use App\Enums\CustomerInvoiceStatus;
use App\Models\CustomerInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveCustomerInvoiceAction
{
    public function handle(CustomerInvoice $invoice, User $actor): CustomerInvoice
    {
        Gate::forUser($actor)->authorize('approve', $invoice);

        return DB::transaction(function () use ($actor, $invoice): CustomerInvoice {
            $invoice = CustomerInvoice::query()->with(['lines', 'adjustments'])->whereKey($invoice)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== CustomerInvoiceStatus::Submitted
                || (int) $invoice->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'Only a different actor may approve a submitted Customer Invoice.']);
            }
            $snapshot = [
                'invoice_number' => $invoice->invoice_number, 'type' => $invoice->type->value,
                'category' => $invoice->category->value, 'customer_id' => $invoice->customer_id,
                'project_id' => $invoice->project_id, 'certificate_number' => $invoice->certificate_number,
                'contract_value_snapshot' => $invoice->contract_value_snapshot,
                'previous_certified_amount' => $invoice->previous_certified_amount,
                'subtotal' => $invoice->subtotal, 'tax_total' => $invoice->tax_total,
                'retention_amount' => $invoice->retention_amount, 'wht_amount' => $invoice->wht_amount,
                'mobilization_recovery_amount' => $invoice->mobilization_recovery_amount,
                'receivable_amount' => $invoice->receivable_amount,
                'lines' => $invoice->lines->map->only([
                    'line_number', 'item_code_snapshot', 'item_name_snapshot', 'quantity',
                    'unit_rate', 'line_subtotal', 'tax_rate_snapshot', 'tax_amount',
                    'revenue_account_id', 'cogs_account_id', 'inventory_site_id',
                ])->all(),
                'adjustments' => $invoice->adjustments->map->only(['type', 'description', 'amount'])->all(),
            ];
            $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
            $invoice->update([
                'status' => CustomerInvoiceStatus::Approved,
                'commercial_snapshot' => $snapshot,
                'commercial_snapshot_hash' => hash('sha256', $encoded),
                'approved_by_id' => $actor->getKey(), 'approved_at' => now(),
            ]);
            activity('customer_invoices')->causedBy($actor)->performedOn($invoice)->event('approved')
                ->withProperties(['company_id' => $invoice->company_id, 'commercial_snapshot_hash' => $invoice->commercial_snapshot_hash])
                ->log('approved Customer Invoice');

            return $invoice->refresh();
        }, attempts: 3);
    }
}
