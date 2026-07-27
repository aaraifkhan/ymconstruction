<?php

namespace App\Actions\Banking;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementLine;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MatchBankStatementLineAction
{
    public function handle(
        BankReconciliation $reconciliation,
        BankStatementLine $statementLine,
        JournalLine $journalLine,
        string $amount,
        User $actor,
    ): BankReconciliationMatch {
        Gate::forUser($actor)->authorize('match', $reconciliation);

        return DB::transaction(function () use ($actor, $amount, $journalLine, $reconciliation, $statementLine): BankReconciliationMatch {
            $reconciliation = BankReconciliation::query()->whereKey($reconciliation)->lockForUpdate()->firstOrFail();
            $statementLine = BankStatementLine::query()->whereKey($statementLine)->lockForUpdate()->firstOrFail();
            $journalLine = JournalLine::query()->with('journalEntry')->whereKey($journalLine)->lockForUpdate()->firstOrFail();
            if (! $reconciliation->isOpen()
                || (int) $statementLine->bank_statement_id !== (int) $reconciliation->bank_statement_id
                || $journalLine->journalEntry->transaction_date->lt($reconciliation->period_start)
                || $journalLine->journalEntry->transaction_date->gt($reconciliation->period_end)) {
                throw ValidationException::withMessages(['journal_line_id' => 'Choose bank activity within this open reconciliation period.']);
            }

            $statementDebit = bccomp((string) $statementLine->debit, '0', 4) === 1;
            $journalAmount = $statementDebit ? (string) $journalLine->credit : (string) $journalLine->debit;
            if (bccomp($journalAmount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['journal_line_id' => 'Bank statement debit/credit direction does not match the journal bank line.']);
            }

            $statementMatched = (string) BankReconciliationMatch::query()
                ->where('bank_statement_line_id', $statementLine->getKey())->sum('amount');
            $journalMatched = (string) BankReconciliationMatch::query()
                ->where('journal_line_id', $journalLine->getKey())->sum('amount');
            $statementRemaining = bcsub($statementLine->statementAmount(), $statementMatched, 4);
            $journalRemaining = bcsub($journalAmount, $journalMatched, 4);
            if (bccomp($amount, '0', 4) !== 1
                || bccomp($amount, $statementRemaining, 4) === 1
                || bccomp($amount, $journalRemaining, 4) === 1) {
                throw ValidationException::withMessages(['amount' => 'Match amount exceeds the remaining statement or journal bank amount.']);
            }

            $match = BankReconciliationMatch::query()->create([
                'bank_reconciliation_id' => $reconciliation->getKey(),
                'company_id' => $reconciliation->company_id,
                'bank_statement_line_id' => $statementLine->getKey(),
                'journal_line_id' => $journalLine->getKey(),
                'amount' => $amount,
                'matched_by_id' => $actor->getKey(),
                'matched_at' => now(),
            ]);
            activity('bank_reconciliations')->causedBy($actor)->performedOn($reconciliation)->event('matched')
                ->withProperties([
                    'company_id' => $reconciliation->company_id,
                    'statement_line_id' => $statementLine->getKey(),
                    'journal_line_id' => $journalLine->getKey(),
                    'amount' => $amount,
                ])->log('matched bank statement to journal');

            return $match;
        }, attempts: 3);
    }
}
