<?php

namespace App\Actions\Banking;

use App\Enums\BankReconciliationStatus;
use App\Enums\BankStatementStatus;
use App\Enums\JournalStatus;
use App\Models\BankReconciliation;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CloseBankReconciliationAction
{
    public function handle(BankReconciliation $reconciliation, User $actor): BankReconciliation
    {
        Gate::forUser($actor)->authorize('close', $reconciliation);

        return DB::transaction(function () use ($actor, $reconciliation): BankReconciliation {
            $reconciliation = BankReconciliation::query()
                ->with(['bankStatement.lines.reconciliationMatches'])
                ->whereKey($reconciliation)->lockForUpdate()->firstOrFail();
            if (! $reconciliation->isOpen()) {
                throw ValidationException::withMessages(['status' => 'Only a draft or reopened reconciliation may be closed.']);
            }
            if ((int) $reconciliation->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['closed_by_id' => 'The reconciliation preparer cannot close the same reconciliation.']);
            }

            foreach ($reconciliation->bankStatement->lines as $statementLine) {
                $matched = (string) $statementLine->reconciliationMatches->sum('amount');
                if (bccomp($matched, $statementLine->statementAmount(), 4) !== 0) {
                    throw ValidationException::withMessages(['matches' => 'Every bank statement line must be fully matched before closing.']);
                }
            }

            $bankLines = JournalLine::query()
                ->where('company_id', $reconciliation->company_id)
                ->where('company_bank_account_id', $reconciliation->company_bank_account_id)
                ->whereHas('journalEntry', fn ($query) => $query
                    ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                    ->whereDate('transaction_date', '<=', $reconciliation->period_end));
            $bookClosing = bcsub(
                (string) (clone $bankLines)->sum('debit'),
                (string) (clone $bankLines)->sum('credit'),
                4,
            );
            $difference = bcsub((string) $reconciliation->bankStatement->closing_balance, $bookClosing, 4);
            if (bccomp($difference, '0', 4) !== 0) {
                throw ValidationException::withMessages(['difference' => 'Statement and General Ledger bank closing balances must agree before closing.']);
            }

            $reconciliation->update([
                'status' => BankReconciliationStatus::Closed,
                'statement_closing_balance' => $reconciliation->bankStatement->closing_balance,
                'book_closing_balance' => $bookClosing,
                'difference' => $difference,
                'closed_by_id' => $actor->getKey(),
                'closed_at' => now(),
            ]);
            $reconciliation->bankStatement->update([
                'status' => BankStatementStatus::Locked,
                'locked_by_id' => $actor->getKey(),
                'locked_at' => now(),
            ]);
            activity('bank_reconciliations')->causedBy($actor)->performedOn($reconciliation)->event('closed')
                ->withProperties([
                    'company_id' => $reconciliation->company_id,
                    'book_closing_balance' => $bookClosing,
                    'statement_closing_balance' => $reconciliation->bankStatement->closing_balance,
                ])->log('closed bank reconciliation');

            return $reconciliation->refresh();
        }, attempts: 3);
    }
}
