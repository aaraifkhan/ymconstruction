<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\FinancialPeriodStatus;
use App\Enums\IntercompanyDirection;
use App\Enums\IntercompanyStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\FinancialPeriod;
use App\Models\IntercompanyTransaction;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostIntercompanyTransactionAction
{
    public function __construct(private PostJournalEntryAction $postJournal) {}

    public function handle(IntercompanyTransaction $transaction, User $actor): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('post', $transaction);

        return DB::transaction(function () use ($transaction, $actor): IntercompanyTransaction {
            $transaction = IntercompanyTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status === IntercompanyStatus::Posted) {
                return $transaction;
            }
            if ($transaction->status !== IntercompanyStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Both company sides must independently approve before posting.']);
            }
            if ((int) $transaction->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot post the paired transaction.']);
            }

            $originPeriod = $this->openPeriod($transaction->company_id, $transaction->transaction_date);
            $counterpartyPeriod = $this->openPeriod($transaction->counterparty_company_id, $transaction->transaction_date);
            $originDueAccountId = $this->mapping(
                $transaction->company_id,
                $transaction->direction === IntercompanyDirection::OriginReceivable
                    ? AccountingMappingKey::DueFromRelatedCompanies
                    : AccountingMappingKey::DueToRelatedCompanies,
            );
            $counterpartyDueAccountId = $this->mapping(
                $transaction->counterparty_company_id,
                $transaction->direction === IntercompanyDirection::OriginReceivable
                    ? AccountingMappingKey::DueToRelatedCompanies
                    : AccountingMappingKey::DueFromRelatedCompanies,
            );

            $origin = $this->createJournal(
                $transaction,
                $transaction->company_id,
                $transaction->counterparty_company_id,
                $originPeriod,
                $originDueAccountId,
                $transaction->origin_offset_account_id,
                $transaction->direction === IntercompanyDirection::OriginReceivable,
                $actor,
                'origin',
            );
            $counterparty = $this->createJournal(
                $transaction,
                $transaction->counterparty_company_id,
                $transaction->company_id,
                $counterpartyPeriod,
                $counterpartyDueAccountId,
                $transaction->counterparty_offset_account_id,
                $transaction->direction === IntercompanyDirection::OriginPayable,
                $actor,
                'counterparty',
            );

            if (bccomp((string) $origin->debit_total, (string) $counterparty->debit_total, 4) !== 0
                || bccomp((string) $origin->credit_total, (string) $counterparty->credit_total, 4) !== 0) {
                throw ValidationException::withMessages(['amount' => 'Paired company journals are out of balance.']);
            }

            $transaction->update([
                'status' => IntercompanyStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'origin_journal_entry_id' => $origin->getKey(),
                'counterparty_journal_entry_id' => $counterparty->getKey(),
            ]);
            activity('intercompany')->causedBy($actor)->performedOn($transaction)->event('posted')
                ->withProperties(['origin_journal_entry_id' => $origin->getKey(), 'counterparty_journal_entry_id' => $counterparty->getKey()])
                ->log('posted balanced inter-company pair');

            return $transaction->refresh();
        }, attempts: 3);
    }

    private function openPeriod(int $companyId, mixed $date): FinancialPeriod
    {
        $period = FinancialPeriod::query()->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
            ->where('status', FinancialPeriodStatus::Open)->lockForUpdate()->first();
        if ($period === null) {
            throw ValidationException::withMessages(['transaction_date' => 'Transaction date must be open in both companies.']);
        }

        return $period;
    }

    private function mapping(int $companyId, AccountingMappingKey $key): int
    {
        $accountId = AccountingMapping::query()->where('company_id', $companyId)
            ->where('system_key', $key)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing {$key->value} mapping for company {$companyId}."]);
        }

        return (int) $accountId;
    }

    private function createJournal(
        IntercompanyTransaction $transaction,
        int $companyId,
        int $relatedCompanyId,
        FinancialPeriod $period,
        int $dueAccountId,
        int $offsetAccountId,
        bool $dueIsDebit,
        User $actor,
        string $side,
    ): JournalEntry {
        $entry = JournalEntry::create([
            'company_id' => $companyId,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::InterCompany,
            'idempotency_key' => Str::uuid(),
            'status' => JournalStatus::Draft,
            'transaction_date' => $transaction->transaction_date,
            'reference' => $transaction->reference,
            'description' => $transaction->description,
            'currency_code' => 'PKR',
            'source_type' => IntercompanyTransaction::class,
            'source_id' => $transaction->getKey(),
            'prepared_by_id' => $transaction->prepared_by_id,
            'submitted_by_id' => $actor->getKey(),
            'submitted_at' => now(),
            'approved_by_id' => $actor->getKey(),
            'approved_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(), 'company_id' => $companyId,
            'related_company_id' => $relatedCompanyId, 'line_number' => 1,
            'account_id' => $dueAccountId, 'description' => "Inter-company {$side} control",
            'debit' => $dueIsDebit ? $transaction->amount : 0,
            'credit' => $dueIsDebit ? 0 : $transaction->amount,
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(), 'company_id' => $companyId,
            'related_company_id' => null, 'line_number' => 2,
            'account_id' => $offsetAccountId, 'description' => $transaction->description,
            'debit' => $dueIsDebit ? 0 : $transaction->amount,
            'credit' => $dueIsDebit ? $transaction->amount : 0,
        ]);
        $entry->update(['status' => JournalStatus::Approved]);

        return $this->postJournal->handle($entry, $actor);
    }
}
