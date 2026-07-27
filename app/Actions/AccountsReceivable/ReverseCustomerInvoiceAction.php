<?php

namespace App\Actions\AccountsReceivable;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Actions\Inventory\ApplyInventoryMovementAction;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Models\CustomerInvoice;
use App\Models\InventoryBalance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseCustomerInvoiceAction
{
    public function __construct(
        private ReverseJournalEntryAction $reverseJournalEntry,
        private ApplyInventoryMovementAction $applyInventoryMovement,
    ) {}

    public function handle(CustomerInvoice $invoice, User $actor, CarbonInterface $date, string $reason): CustomerInvoice
    {
        Gate::forUser($actor)->authorize('reverse', $invoice);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($actor, $date, $invoice, $reason): CustomerInvoice {
            $invoice = CustomerInvoice::query()->whereKey($invoice)->lockForUpdate()->firstOrFail();
            if ($invoice->status === CustomerInvoiceStatus::Reversed) {
                return $invoice;
            }
            if ($invoice->status !== CustomerInvoiceStatus::Posted || $invoice->journal_entry_id === null) {
                throw ValidationException::withMessages(['status' => 'Only a posted Customer Invoice may be reversed.']);
            }
            if (bccomp($invoice->settledAmount(), '0', 4) === 1
                || ($invoice->type === CustomerInvoiceType::Invoice && bccomp($invoice->creditedAmount(), '0', 4) === 1)) {
                throw ValidationException::withMessages(['status' => 'Reverse posted receipts and Credit Notes before reversing the source invoice.']);
            }
            $reversal = $this->reverseJournalEntry->handle($invoice->journalEntry, $actor, $date, $reason);
            $this->reverseTradingInventory($invoice, $actor);
            $invoice->update([
                'status' => CustomerInvoiceStatus::Reversed,
                'reversal_journal_entry_id' => $reversal->getKey(),
                'reversed_by_id' => $actor->getKey(), 'reversed_at' => now(),
            ]);
            activity('customer_invoices')->causedBy($actor)->performedOn($invoice)->event('reversed')
                ->withProperties(['company_id' => $invoice->company_id, 'reason' => $reason, 'reversal_journal_entry_id' => $reversal->getKey()])
                ->log('reversed Customer Invoice');

            return $invoice->refresh();
        }, attempts: 3);
    }

    private function reverseTradingInventory(CustomerInvoice $invoice, User $actor): void
    {
        if ($invoice->category !== CustomerInvoiceCategory::TradingSale) {
            return;
        }
        foreach ($invoice->lines()->orderBy('item_id')->lockForUpdate()->get() as $line) {
            if ($invoice->type === CustomerInvoiceType::Invoice) {
                $this->applyInventoryMovement->handle(
                    $invoice->company_id, (int) $line->inventory_site_id, (int) $line->item_id,
                    InventoryMovementDirection::In, (string) $line->quantity, (string) $line->cogs_unit_cost,
                    InventoryMovementType::TradingSaleReversal, $line, $actor, projectId: $invoice->project_id,
                );

                continue;
            }
            $averageCost = (string) InventoryBalance::query()
                ->where('company_id', $invoice->company_id)->where('project_site_id', $line->inventory_site_id)
                ->where('item_id', $line->item_id)->lockForUpdate()->value('average_unit_cost');
            if (bccomp($averageCost, (string) $line->cogs_unit_cost, 4) !== 0) {
                throw ValidationException::withMessages(['inventory' => 'Credit Note reversal requires unchanged average cost; use a controlled inventory adjustment when later stock activity changed valuation.']);
            }
            $this->applyInventoryMovement->handle(
                $invoice->company_id, (int) $line->inventory_site_id, (int) $line->item_id,
                InventoryMovementDirection::Out, (string) $line->quantity, null,
                InventoryMovementType::TradingSaleReversal, $line, $actor, projectId: $invoice->project_id,
            );
        }
    }
}
