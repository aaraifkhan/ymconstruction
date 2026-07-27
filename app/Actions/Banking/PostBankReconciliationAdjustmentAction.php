<?php

namespace App\Actions\Banking;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementLine;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostBankReconciliationAdjustmentAction
{
    public function __construct(
        private PostJournalEntryAction $postJournalEntry,
        private MatchBankStatementLineAction $matchStatementLine,
    ) {}

    public function handle(
        BankReconciliation $reconciliation,
        BankStatementLine $statementLine,
        Account $adjustmentAccount,
        string $reason,
        User $actor,
    ): BankReconciliationMatch {
        Gate::forUser($actor)->authorize('adjust', $reconciliation);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'An adjustment reason is required.']);
        }

        return DB::transaction(function () use ($actor, $adjustmentAccount, $reason, $reconciliation, $statementLine): BankReconciliationMatch {
            $reconciliation = BankReconciliation::query()->whereKey($reconciliation)->lockForUpdate()->firstOrFail();
            $statementLine = BankStatementLine::query()->whereKey($statementLine)->lockForUpdate()->firstOrFail();
            if (! $reconciliation->isOpen()
                || (int) $statementLine->bank_statement_id !== (int) $reconciliation->bank_statement_id
                || (int) $reconciliation->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'Adjustment requires an open reconciliation and an actor different from its preparer.']);
            }
            $adjustmentAccount = Account::query()->whereKey($adjustmentAccount)
                ->where('company_id', $reconciliation->company_id)
                ->where('is_active', true)->where('allows_manual_posting', true)->first();
            if ($adjustmentAccount === null) {
                throw ValidationException::withMessages(['adjustment_account_id' => 'Choose an active same-company manual adjustment account.']);
            }

            $alreadyMatched = (string) BankReconciliationMatch::query()
                ->where('bank_statement_line_id', $statementLine->getKey())->sum('amount');
            $remaining = bcsub($statementLine->statementAmount(), $alreadyMatched, 4);
            if (bccomp($remaining, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'The statement line is already fully matched.']);
            }

            $period = FinancialPeriod::query()
                ->where('company_id', $reconciliation->company_id)
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $statementLine->transaction_date)
                ->whereDate('ends_on', '>=', $statementLine->transaction_date)
                ->lockForUpdate()->first();
            if ($period === null) {
                throw ValidationException::withMessages(['transaction_date' => 'Adjustment date must belong to an open company period.']);
            }
            $bankAccountId = AccountingMapping::query()
                ->where('company_id', $reconciliation->company_id)
                ->where('company_bank_account_id', $reconciliation->company_bank_account_id)
                ->where('is_active', true)->value('account_id');
            if ($bankAccountId === null) {
                throw ValidationException::withMessages(['company_bank_account_id' => 'The bank account requires an active GL mapping.']);
            }

            $journal = JournalEntry::query()->create([
                'company_id' => $reconciliation->company_id,
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Journal,
                'idempotency_key' => Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $statementLine->transaction_date,
                'reference' => $statementLine->bank_reference,
                'description' => "Bank reconciliation adjustment: {$reason}",
                'currency_code' => 'PKR',
                'source_type' => $reconciliation::class,
                'source_id' => $reconciliation->getKey(),
                'prepared_by_id' => $reconciliation->prepared_by_id,
            ]);
            $statementDebit = bccomp((string) $statementLine->debit, '0', 4) === 1;
            $journal->lines()->create([
                'company_id' => $reconciliation->company_id,
                'line_number' => 1,
                'account_id' => $statementDebit ? $adjustmentAccount->getKey() : $bankAccountId,
                'description' => $reason,
                'debit' => $remaining,
                'credit' => '0.0000',
                'company_bank_account_id' => $statementDebit ? null : $reconciliation->company_bank_account_id,
            ]);
            $journal->lines()->create([
                'company_id' => $reconciliation->company_id,
                'line_number' => 2,
                'account_id' => $statementDebit ? $bankAccountId : $adjustmentAccount->getKey(),
                'description' => $reason,
                'debit' => '0.0000',
                'credit' => $remaining,
                'company_bank_account_id' => $statementDebit ? $reconciliation->company_bank_account_id : null,
            ]);
            $journal->update([
                'status' => JournalStatus::Approved,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $journal = $this->postJournalEntry->handle($journal, $actor);
            $bankLine = $journal->lines()->where('company_bank_account_id', $reconciliation->company_bank_account_id)->firstOrFail();
            $match = $this->matchStatementLine->handle($reconciliation, $statementLine, $bankLine, $remaining, $actor);
            activity('bank_reconciliations')->causedBy($actor)->performedOn($reconciliation)->event('adjusted')
                ->withProperties([
                    'company_id' => $reconciliation->company_id,
                    'statement_line_id' => $statementLine->getKey(),
                    'journal_entry_id' => $journal->getKey(),
                    'adjustment_account_id' => $adjustmentAccount->getKey(),
                    'amount' => $remaining,
                    'reason' => $reason,
                ])->log('posted bank reconciliation adjustment');

            return $match;
        }, attempts: 3);
    }
}
