<?php

namespace App\Actions\AccountsReceivable;

use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\ItemType;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitCustomerInvoiceAction
{
    public function __construct(
        private CalculateCustomerInvoiceTotalsAction $calculateTotals,
        private ReserveCustomerInvoiceNumberAction $reserveNumber,
    ) {}

    public function handle(CustomerInvoice $invoice, User $actor): CustomerInvoice
    {
        Gate::forUser($actor)->authorize('submit', $invoice);

        return DB::transaction(function () use ($actor, $invoice): CustomerInvoice {
            $invoice = CustomerInvoice::query()->whereKey($invoice)->lockForUpdate()->firstOrFail();
            if (! $invoice->isEditable() || (int) $invoice->prepared_by_id !== (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Only the preparer may submit an editable Customer Invoice.']);
            }
            $lines = $invoice->lines()->orderBy('id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'At least one Customer Invoice line is required.']);
            }
            if ($invoice->category === CustomerInvoiceCategory::RunningBill
                && (blank($invoice->certificate_number) || $invoice->certificate_date === null)) {
                throw ValidationException::withMessages(['certificate_number' => 'A Running Bill requires certificate number and date.']);
            }
            foreach ($lines as $line) {
                if ($invoice->category === CustomerInvoiceCategory::ServiceInvoice && $line->item?->type !== ItemType::Service) {
                    throw ValidationException::withMessages(['lines' => 'Service Invoice lines require configured Service items.']);
                }
                if ($invoice->type === CustomerInvoiceType::CreditNote) {
                    $this->validateCreditLine($invoice, $line);
                }
            }
            if ($invoice->type === CustomerInvoiceType::CreditNote
                && $invoice->originalCustomerInvoice?->status !== CustomerInvoiceStatus::Posted) {
                throw ValidationException::withMessages(['original_customer_invoice_id' => 'Credit Note source must be posted.']);
            }

            $invoice = $this->calculateTotals->handle($invoice);
            if (bccomp((string) $invoice->gross_total, '0', 4) !== 1) {
                throw ValidationException::withMessages(['lines' => 'Customer Invoice total must be positive.']);
            }
            $invoice->update([
                'invoice_number' => $invoice->invoice_number ?? $this->reserveNumber->handle($invoice),
                'status' => CustomerInvoiceStatus::Submitted,
                'submitted_by_id' => $actor->getKey(), 'submitted_at' => now(),
                'approved_by_id' => null, 'approved_at' => null,
                'rejected_by_id' => null, 'rejected_at' => null, 'rejection_reason' => null,
            ]);
            activity('customer_invoices')->causedBy($actor)->performedOn($invoice)->event('submitted')
                ->withProperties([
                    'company_id' => $invoice->company_id, 'invoice_number' => $invoice->invoice_number,
                    'category' => $invoice->category->value, 'gross_total' => $invoice->gross_total,
                ])->log('submitted Customer Invoice');

            return $invoice->refresh();
        }, attempts: 3);
    }

    private function validateCreditLine(CustomerInvoice $invoice, CustomerInvoiceLine $line): void
    {
        $original = CustomerInvoiceLine::query()->whereKey($line->original_customer_invoice_line_id)
            ->where('customer_invoice_id', $invoice->original_customer_invoice_id)
            ->where('company_id', $invoice->company_id)->lockForUpdate()->first();
        if ($original === null || (int) $original->item_id !== (int) $line->item_id
            || (int) $original->revenue_account_id !== (int) $line->revenue_account_id) {
            throw ValidationException::withMessages(['original_customer_invoice_line_id' => 'Credit line must match a source line item and revenue account.']);
        }
        $otherCredits = (string) $original->creditLines()->where('customer_invoice_id', '!=', $invoice->getKey())
            ->whereHas('customerInvoice', fn ($query) => $query->whereIn('status', [
                CustomerInvoiceStatus::Submitted->value,
                CustomerInvoiceStatus::Approved->value,
                CustomerInvoiceStatus::Posted->value,
            ]))->sum('quantity');
        if (bccomp(bcadd($otherCredits, (string) $line->quantity, 4), (string) $original->quantity, 4) === 1) {
            throw ValidationException::withMessages(['quantity' => 'Credit Note quantity exceeds the original line quantity available to credit.']);
        }
    }
}
