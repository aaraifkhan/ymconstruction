<?php

namespace App\Actions\Banking;

use App\Models\BankReconciliationMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UnmatchBankStatementLineAction
{
    public function handle(BankReconciliationMatch $match, User $actor): void
    {
        Gate::forUser($actor)->authorize('unmatch', $match->bankReconciliation);

        DB::transaction(function () use ($actor, $match): void {
            $match = BankReconciliationMatch::query()->with('bankReconciliation')
                ->whereKey($match)->lockForUpdate()->firstOrFail();
            $reconciliation = $match->bankReconciliation;
            $evidence = [
                'company_id' => $match->company_id,
                'statement_line_id' => $match->bank_statement_line_id,
                'journal_line_id' => $match->journal_line_id,
                'amount' => $match->amount,
            ];
            $match->delete();
            activity('bank_reconciliations')->causedBy($actor)->performedOn($reconciliation)->event('unmatched')
                ->withProperties($evidence)->log('removed bank reconciliation match');
        }, attempts: 3);
    }
}
