<?php

namespace App\Actions\Treasury;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\HR\RecordEmployeeFinancingTreasuryAction;
use App\Enums\AccountingMappingKey;
use App\Enums\FinalSettlementStatus;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\PartyRole;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\FinalSettlement;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostTreasuryTransactionAction
{
    public function __construct(
        private PostJournalEntryAction $postJournalEntry,
        private RecordEmployeeFinancingTreasuryAction $recordEmployeeFinancing,
    ) {}

    public function handle(TreasuryTransaction $transaction, User $actor): TreasuryTransaction
    {
        Gate::forUser($actor)->authorize('post', $transaction);

        return DB::transaction(function () use ($actor, $transaction): TreasuryTransaction {
            $transaction = TreasuryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status === TreasuryStatus::Posted) {
                return $transaction;
            }
            if ($transaction->status !== TreasuryStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved treasury transaction may be posted.']);
            }
            if ((int) $transaction->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['posted_by_id' => 'The preparer cannot post the same treasury transaction.']);
            }

            $journal = $this->journalFor($transaction, $actor);
            $transaction->update([
                'transaction_number' => $journal->voucher_number,
                'status' => TreasuryStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'journal_entry_id' => $journal->getKey(),
            ]);
            $this->recordEmployeeFinancing->handle($transaction, $actor);
            $this->refreshFinalSettlements($transaction);

            activity('treasury_transactions')->causedBy($actor)->performedOn($transaction)->event('posted')
                ->withProperties([
                    'company_id' => $transaction->company_id,
                    'transaction_number' => $journal->voucher_number,
                    'journal_entry_id' => $journal->getKey(),
                    'amount' => $transaction->amount,
                ])->log('posted treasury transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }

    private function journalFor(TreasuryTransaction $transaction, User $actor): JournalEntry
    {
        $idempotencyKey = "TreasuryTransaction:{$transaction->getKey()}:posting";
        $existing = JournalEntry::query()->where('company_id', $transaction->company_id)
            ->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
        if ($existing !== null) {
            return $existing->status === JournalStatus::Posted
                ? $existing
                : $this->postJournalEntry->handle($existing, $actor);
        }

        $period = FinancialPeriod::query()
            ->where('company_id', $transaction->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $transaction->transaction_date)
            ->whereDate('ends_on', '>=', $transaction->transaction_date)
            ->lockForUpdate()
            ->first();
        if ($period === null) {
            throw ValidationException::withMessages(['transaction_date' => 'An open financial period is required for the treasury date.']);
        }

        $journal = JournalEntry::query()->create([
            'company_id' => $transaction->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => match ($transaction->type) {
                TreasuryTransactionType::Payment => VoucherType::Payment,
                TreasuryTransactionType::Receipt => VoucherType::Receipt,
                TreasuryTransactionType::Transfer => VoucherType::Contra,
            },
            'idempotency_key' => $idempotencyKey,
            'status' => JournalStatus::Draft,
            'transaction_date' => $transaction->transaction_date,
            'reference' => $transaction->bank_reference ?? $transaction->external_reference,
            'description' => $transaction->description,
            'currency_code' => $transaction->currency_code,
            'source_type' => $transaction::class,
            'source_id' => $transaction->getKey(),
            'prepared_by_id' => $transaction->prepared_by_id,
        ]);

        $lineNumber = 1;
        if ($transaction->type === TreasuryTransactionType::Transfer) {
            $this->line($journal, $lineNumber++, (int) $transaction->destination_account_id, (string) $transaction->amount, false, $transaction, true);
            $this->line($journal, $lineNumber, (int) $transaction->source_account_id, (string) $transaction->amount, true, $transaction, true);
        } else {
            foreach ($transaction->allocations()->with('allocatable')->get() as $allocation) {
                $isReceiptAllocation = in_array($allocation->allocation_type, [
                    TreasuryAllocationType::CustomerInvoice,
                    TreasuryAllocationType::FinalSettlement,
                ], true) && $transaction->type === TreasuryTransactionType::Receipt;
                $this->line(
                    $journal,
                    $lineNumber++,
                    $this->mappedAccountId(
                        $transaction->company_id,
                        match ($allocation->allocation_type) {
                            TreasuryAllocationType::CustomerInvoice => AccountingMappingKey::AccountsReceivable,
                            TreasuryAllocationType::PayrollEntry => AccountingMappingKey::SalaryPayable,
                            TreasuryAllocationType::FinalSettlement => $allocation->allocatable->balance_direction === 'receivable'
                                ? AccountingMappingKey::EmployeeAdvances
                                : AccountingMappingKey::SalaryPayable,
                            default => AccountingMappingKey::AccountsPayable,
                        },
                    ),
                    (string) $allocation->amount,
                    $isReceiptAllocation,
                    $transaction,
                );
            }

            if (bccomp((string) $transaction->unallocated_amount, '0', 4) === 1) {
                $offsetAccountId = $this->offsetAccountId($transaction);
                $this->line(
                    $journal,
                    $lineNumber++,
                    $offsetAccountId,
                    (string) $transaction->unallocated_amount,
                    $transaction->type === TreasuryTransactionType::Receipt,
                    $transaction,
                );
            }

            $liquidAccountId = $transaction->type === TreasuryTransactionType::Payment
                ? (int) $transaction->source_account_id
                : (int) $transaction->destination_account_id;
            $this->line(
                $journal,
                $lineNumber,
                $liquidAccountId,
                (string) $transaction->amount,
                $transaction->type === TreasuryTransactionType::Payment,
                $transaction,
                true,
            );
        }

        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $transaction->submitted_by_id,
            'submitted_at' => $transaction->submitted_at,
            'approved_by_id' => $transaction->approved_by_id,
            'approved_at' => $transaction->approved_at,
        ]);

        return $this->postJournalEntry->handle($journal, $actor);
    }

    private function offsetAccountId(TreasuryTransaction $transaction): int
    {
        if ($transaction->purpose !== TreasuryPurpose::Advance) {
            return $transaction->type === TreasuryTransactionType::Payment
                ? (int) $transaction->destination_account_id
                : (int) $transaction->source_account_id;
        }

        if ($transaction->employment_id !== null && in_array($transaction->type, [
            TreasuryTransactionType::Payment,
            TreasuryTransactionType::Receipt,
        ], true)) {
            return $this->mappedAccountId($transaction->company_id, AccountingMappingKey::EmployeeAdvances);
        }

        if ($transaction->party?->hasRole(PartyRole::Vendor) && $transaction->type === TreasuryTransactionType::Payment) {
            return $this->mappedAccountId($transaction->company_id, AccountingMappingKey::VendorAdvances);
        }

        if ($transaction->party?->hasRole(PartyRole::Customer) && $transaction->type === TreasuryTransactionType::Receipt) {
            return $this->mappedAccountId($transaction->company_id, AccountingMappingKey::CustomerAdvances);
        }

        throw ValidationException::withMessages(['purpose' => 'Advance direction requires a Vendor payment, Employee payment, or Customer receipt.']);
    }

    private function line(
        JournalEntry $journal,
        int $lineNumber,
        int $accountId,
        string $amount,
        bool $credit,
        TreasuryTransaction $transaction,
        bool $liquid = false,
    ): void {
        $journal->lines()->create([
            'company_id' => $transaction->company_id,
            'line_number' => $lineNumber,
            'account_id' => $accountId,
            'description' => $transaction->description,
            'debit' => $credit ? '0.0000' : $amount,
            'credit' => $credit ? $amount : '0.0000',
            'party_id' => $transaction->party_id,
            'employment_id' => $transaction->employment_id,
            'company_bank_account_id' => $liquid
                ? ($accountId === (int) $transaction->source_account_id
                    ? $transaction->source_company_bank_account_id
                    : $transaction->destination_company_bank_account_id)
                : null,
        ]);
    }

    private function mappedAccountId(int $companyId, AccountingMappingKey $key): int
    {
        $accountId = AccountingMapping::query()->where('company_id', $companyId)
            ->where('system_key', $key)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing active {$key->value} accounting mapping."]);
        }

        return (int) $accountId;
    }

    private function refreshFinalSettlements(TreasuryTransaction $transaction): void
    {
        $settlements = FinalSettlement::query()->whereIn(
            'id',
            $transaction->allocations()
                ->where('allocation_type', TreasuryAllocationType::FinalSettlement)
                ->pluck('allocatable_id'),
        )->lockForUpdate()->get();
        foreach ($settlements as $settlement) {
            if (bccomp($settlement->postedOpenAmount(), '0', 4) === 0) {
                $settlement->update(['status' => FinalSettlementStatus::Settled]);
            }
        }
    }
}
