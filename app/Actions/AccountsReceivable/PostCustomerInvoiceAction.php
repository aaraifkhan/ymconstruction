<?php

namespace App\Actions\AccountsReceivable;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Inventory\ApplyInventoryMovementAction;
use App\Enums\AccountingMappingKey;
use App\Enums\CustomerInvoiceAdjustmentType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\FinancialPeriodStatus;
use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostCustomerInvoiceAction
{
    public function __construct(
        private PostJournalEntryAction $postJournalEntry,
        private ApplyInventoryMovementAction $applyInventoryMovement,
    ) {}

    public function handle(CustomerInvoice $invoice, User $actor): CustomerInvoice
    {
        Gate::forUser($actor)->authorize('post', $invoice);

        return DB::transaction(function () use ($actor, $invoice): CustomerInvoice {
            $invoice = CustomerInvoice::query()->whereKey($invoice)->lockForUpdate()->firstOrFail();
            if ($invoice->status === CustomerInvoiceStatus::Posted) {
                return $invoice;
            }
            if ($invoice->status !== CustomerInvoiceStatus::Approved
                || (int) $invoice->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Only a different actor may post an approved Customer Invoice.']);
            }

            $this->applyTradingInventory($invoice, $actor);
            $journal = $this->journalFor($invoice, $actor);
            $invoice->update([
                'status' => CustomerInvoiceStatus::Posted, 'posted_by_id' => $actor->getKey(),
                'posted_at' => now(), 'journal_entry_id' => $journal->getKey(),
            ]);
            activity('customer_invoices')->causedBy($actor)->performedOn($invoice)->event('posted')
                ->withProperties([
                    'company_id' => $invoice->company_id, 'journal_entry_id' => $journal->getKey(),
                    'receivable_amount' => $invoice->receivable_amount,
                    'cogs_amount' => $invoice->lines()->sum('cogs_amount'),
                ])->log('posted Customer Invoice to Accounts Receivable');

            return $invoice->refresh();
        }, attempts: 3);
    }

    private function applyTradingInventory(CustomerInvoice $invoice, User $actor): void
    {
        if ($invoice->category !== CustomerInvoiceCategory::TradingSale) {
            return;
        }
        foreach ($invoice->lines()->orderBy('item_id')->lockForUpdate()->get() as $line) {
            if ($invoice->type === CustomerInvoiceType::Invoice) {
                $movement = $this->applyInventoryMovement->handle(
                    $invoice->company_id, (int) $line->inventory_site_id, (int) $line->item_id,
                    InventoryMovementDirection::Out, (string) $line->quantity, null,
                    InventoryMovementType::TradingSale, $line, $actor, projectId: $invoice->project_id,
                );
            } else {
                $original = $line->originalCustomerInvoiceLine()->lockForUpdate()->firstOrFail();
                $movement = $this->applyInventoryMovement->handle(
                    $invoice->company_id, (int) $line->inventory_site_id, (int) $line->item_id,
                    InventoryMovementDirection::In, (string) $line->quantity, (string) $original->cogs_unit_cost,
                    InventoryMovementType::TradingSaleReturn, $line, $actor, projectId: $invoice->project_id,
                );
            }
            CustomerInvoiceLine::query()->whereKey($line)->update([
                'cogs_unit_cost' => $movement->unit_cost,
                'cogs_amount' => $movement->movement_value,
            ]);
        }
    }

    private function journalFor(CustomerInvoice $invoice, User $actor): JournalEntry
    {
        $idempotencyKey = "CustomerInvoice:{$invoice->getKey()}:posting";
        $existing = JournalEntry::query()->where('company_id', $invoice->company_id)
            ->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
        if ($existing !== null) {
            return $existing->status === JournalStatus::Posted
                ? $existing
                : $this->postJournalEntry->handle($existing, $actor);
        }
        $period = FinancialPeriod::query()->where('company_id', $invoice->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $invoice->invoice_date)
            ->whereDate('ends_on', '>=', $invoice->invoice_date)->lockForUpdate()->first();
        if ($period === null) {
            throw ValidationException::withMessages(['invoice_date' => 'An open financial period is required for the Customer Invoice date.']);
        }
        $journal = JournalEntry::query()->create([
            'company_id' => $invoice->company_id, 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => $invoice->type === CustomerInvoiceType::Invoice ? VoucherType::Sales : VoucherType::CreditNote,
            'idempotency_key' => $idempotencyKey, 'status' => JournalStatus::Draft,
            'transaction_date' => $invoice->invoice_date,
            'reference' => $invoice->customer_reference ?? $invoice->certificate_number,
            'description' => "{$invoice->invoice_number} — {$invoice->category->label()}",
            'currency_code' => 'PKR', 'source_type' => $invoice::class,
            'source_id' => $invoice->getKey(), 'prepared_by_id' => $invoice->prepared_by_id,
        ]);

        $isCredit = $invoice->type === CustomerInvoiceType::CreditNote;
        $lineNumber = 1;
        foreach ($invoice->lines()->get() as $line) {
            $this->line($journal, $lineNumber++, (int) $line->revenue_account_id, (string) $line->line_subtotal, ! $isCredit, $line->item_name_snapshot, $invoice);
        }
        if (bccomp((string) $invoice->tax_total, '0', 4) === 1) {
            $this->line($journal, $lineNumber++, $this->mappedAccountId($invoice, AccountingMappingKey::OutputTax), (string) $invoice->tax_total, ! $isCredit, 'Output sales tax', $invoice);
        }
        foreach ($invoice->adjustments()->get() as $adjustment) {
            $mapping = match ($adjustment->type) {
                CustomerInvoiceAdjustmentType::Retention => AccountingMappingKey::RetentionReceivable,
                CustomerInvoiceAdjustmentType::WithholdingTax => AccountingMappingKey::WhtReceivable,
                CustomerInvoiceAdjustmentType::MobilizationRecovery => AccountingMappingKey::CustomerAdvances,
            };
            $this->line($journal, $lineNumber++, $this->mappedAccountId($invoice, $mapping), (string) $adjustment->amount, $isCredit, $adjustment->description, $invoice);
        }
        $this->line(
            $journal, $lineNumber++, $this->mappedAccountId($invoice, AccountingMappingKey::AccountsReceivable),
            (string) $invoice->receivable_amount, $isCredit, 'Customer receivable', $invoice,
        );
        if ($invoice->category === CustomerInvoiceCategory::TradingSale) {
            foreach ($invoice->lines()->get() as $line) {
                $this->line($journal, $lineNumber++, (int) $line->cogs_account_id, (string) $line->cogs_amount, $isCredit, "COGS — {$line->item_name_snapshot}", $invoice);
                $this->line($journal, $lineNumber++, $this->mappedAccountId($invoice, AccountingMappingKey::SiteInventory), (string) $line->cogs_amount, ! $isCredit, "Inventory — {$line->item_name_snapshot}", $invoice);
            }
        }
        $journal->update([
            'status' => JournalStatus::Approved, 'submitted_by_id' => $invoice->submitted_by_id,
            'submitted_at' => $invoice->submitted_at, 'approved_by_id' => $invoice->approved_by_id,
            'approved_at' => $invoice->approved_at,
        ]);

        return $this->postJournalEntry->handle($journal, $actor);
    }

    private function line(
        JournalEntry $journal,
        int $lineNumber,
        int $accountId,
        string $amount,
        bool $credit,
        string $description,
        CustomerInvoice $invoice,
    ): void {
        if (bccomp($amount, '0', 4) !== 1) {
            return;
        }
        $journal->lines()->create([
            'company_id' => $invoice->company_id, 'line_number' => $lineNumber,
            'account_id' => $accountId, 'description' => $description,
            'debit' => $credit ? '0.0000' : $amount, 'credit' => $credit ? $amount : '0.0000',
            'party_id' => $invoice->customer_id, 'project_id' => $invoice->project_id,
            'project_site_id' => $invoice->project_site_id,
        ]);
    }

    private function mappedAccountId(CustomerInvoice $invoice, AccountingMappingKey $mapping): int
    {
        $accountId = AccountingMapping::query()->where('company_id', $invoice->company_id)
            ->where('system_key', $mapping)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing active {$mapping->value} accounting mapping."]);
        }

        return (int) $accountId;
    }
}
