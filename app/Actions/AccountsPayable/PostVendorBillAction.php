<?php

namespace App\Actions\AccountsPayable;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AccountingMappingKey;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostVendorBillAction
{
    public function __construct(private PostJournalEntryAction $postJournalEntry) {}

    public function handle(VendorBill $bill, User $actor): VendorBill
    {
        Gate::forUser($actor)->authorize('post', $bill);

        return DB::transaction(function () use ($actor, $bill): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if ($bill->status === VendorBillStatus::Posted) {
                return $bill;
            }
            if ($bill->status !== VendorBillStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved Vendor Bill may be posted.']);
            }
            if ((int) $bill->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['posted_by_id' => 'The Vendor Bill preparer cannot post the same bill.']);
            }

            $journal = $this->journalFor($bill, $actor);
            $bill->update([
                'status' => VendorBillStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'journal_entry_id' => $journal->getKey(),
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('posted')
                ->withProperties([
                    'company_id' => $bill->company_id,
                    'journal_entry_id' => $journal->getKey(),
                    'voucher_number' => $journal->voucher_number,
                    'net_payable' => $bill->net_payable,
                ])->log('posted Vendor Bill to Accounts Payable');

            return $bill->refresh();
        }, attempts: 3);
    }

    private function journalFor(VendorBill $bill, User $actor): JournalEntry
    {
        $idempotencyKey = "VendorBill:{$bill->getKey()}:posting";
        $existing = JournalEntry::query()
            ->where('company_id', $bill->company_id)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
        if ($existing !== null) {
            return $existing->status === JournalStatus::Posted
                ? $existing
                : $this->postJournalEntry->handle($existing, $actor);
        }

        $period = FinancialPeriod::query()
            ->where('company_id', $bill->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $bill->invoice_date)
            ->whereDate('ends_on', '>=', $bill->invoice_date)
            ->with('financialYear')
            ->lockForUpdate()
            ->first();
        if ($period === null) {
            throw ValidationException::withMessages(['invoice_date' => 'An open financial period is required for the Vendor Bill date.']);
        }

        $journal = JournalEntry::query()->create([
            'company_id' => $bill->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => $bill->type === VendorBillType::Invoice ? VoucherType::Purchase : VoucherType::CreditNote,
            'idempotency_key' => $idempotencyKey,
            'status' => JournalStatus::Draft,
            'transaction_date' => $bill->invoice_date,
            'reference' => $bill->vendor_invoice_number,
            'description' => "{$bill->vendor_bill_number} — vendor {$bill->vendor_invoice_number}",
            'currency_code' => $bill->currency_code,
            'source_type' => $bill::class,
            'source_id' => $bill->getKey(),
            'prepared_by_id' => $bill->prepared_by_id,
        ]);

        $isCreditNote = $bill->type === VendorBillType::CreditNote;
        $lineNumber = 1;
        foreach ($bill->lines()->with(['clearingAccount', 'varianceAccount'])->get() as $billLine) {
            $lineNumber = $this->createCostLines($journal, $bill, $billLine, $lineNumber, $isCreditNote);
        }
        if (bccomp((string) $bill->tax_total, '0', 4) === 1) {
            $this->createJournalLine(
                $journal,
                $lineNumber++,
                $this->mappedAccountId($bill->company_id, AccountingMappingKey::InputTax),
                (string) $bill->tax_total,
                $isCreditNote,
                'Recoverable input tax',
                $bill,
            );
        }
        foreach ($bill->deductions()->get() as $deduction) {
            $accountId = $deduction->account_id
                ?? $this->mappedAccountId(
                    $bill->company_id,
                    $deduction->type->mappingKey()
                        ?? throw ValidationException::withMessages(['account_id' => 'Other deduction account is required.']),
                );
            $this->createJournalLine(
                $journal,
                $lineNumber++,
                $accountId,
                (string) $deduction->amount,
                ! $isCreditNote,
                $deduction->description,
                $bill,
            );
        }
        $this->createJournalLine(
            $journal,
            $lineNumber,
            $this->mappedAccountId($bill->company_id, AccountingMappingKey::AccountsPayable),
            (string) $bill->net_payable,
            ! $isCreditNote,
            'Vendor payable',
            $bill,
        );

        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $bill->submitted_by_id,
            'submitted_at' => $bill->submitted_at,
            'approved_by_id' => $bill->approved_by_id,
            'approved_at' => $bill->approved_at,
        ]);

        return $this->postJournalEntry->handle($journal, $actor);
    }

    private function createCostLines(
        JournalEntry $journal,
        VendorBill $bill,
        VendorBillLine $billLine,
        int $lineNumber,
        bool $isCreditNote,
    ): int {
        $baseAmount = bccomp((string) $billLine->receipt_value, '0', 4) === 1
            ? (string) $billLine->receipt_value
            : (string) $billLine->line_subtotal;
        $this->createJournalLine(
            $journal,
            $lineNumber++,
            (int) $billLine->clearing_account_id,
            $baseAmount,
            $isCreditNote,
            $billLine->description ?? $billLine->item_name_snapshot,
            $bill,
            $billLine,
        );

        $variance = (string) $billLine->price_variance;
        if (bccomp($variance, '0', 4) !== 0) {
            $absoluteVariance = bccomp($variance, '0', 4) === 1 ? $variance : bcmul($variance, '-1', 4);
            $varianceIsCredit = bccomp($variance, '0', 4) === -1;
            if ($isCreditNote) {
                $varianceIsCredit = ! $varianceIsCredit;
            }
            $this->createJournalLine(
                $journal,
                $lineNumber++,
                (int) $billLine->variance_account_id,
                $absoluteVariance,
                $varianceIsCredit,
                'Purchase price variance',
                $bill,
                $billLine,
            );
        }

        return $lineNumber;
    }

    private function createJournalLine(
        JournalEntry $journal,
        int $lineNumber,
        int $accountId,
        string $amount,
        bool $isCredit,
        string $description,
        VendorBill $bill,
        ?VendorBillLine $billLine = null,
    ): void {
        if (bccomp($amount, '0', 4) !== 1) {
            return;
        }

        $journal->lines()->create([
            'company_id' => $bill->company_id,
            'line_number' => $lineNumber,
            'account_id' => $accountId,
            'description' => $description,
            'debit' => $isCredit ? '0.0000' : $amount,
            'credit' => $isCredit ? $amount : '0.0000',
            'party_id' => $bill->vendor_id,
            'project_id' => $billLine?->project_id ?? $bill->project_id,
            'project_site_id' => $billLine?->project_site_id ?? $bill->project_site_id,
            'cost_center_id' => $billLine?->cost_center_id,
        ]);
    }

    private function mappedAccountId(int $companyId, AccountingMappingKey $key): int
    {
        $accountId = AccountingMapping::query()
            ->where('company_id', $companyId)
            ->where('system_key', $key)
            ->where('is_active', true)
            ->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing active {$key->value} accounting mapping."]);
        }

        return (int) $accountId;
    }
}
