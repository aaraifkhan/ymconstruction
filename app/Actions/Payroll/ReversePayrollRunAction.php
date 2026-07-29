<?php

namespace App\Actions\Payroll;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReversePayrollRunAction
{
    public function __construct(
        private ReverseJournalEntryAction $reverseJournalEntry,
        private ReversePayrollFinancingRecoveriesAction $reverseFinancingRecoveries,
    ) {}

    public function handle(PayrollRun $payrollRun, User $actor, CarbonInterface $reversalDate, string $reason): PayrollRun
    {
        Gate::forUser($actor)->authorize('reverse', $payrollRun);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($actor, $payrollRun, $reason, $reversalDate): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            if ($run->reversal_journal_entry_id !== null) {
                return $run;
            }
            if ($run->status !== PayrollRunStatus::Approved || $run->journal_entry_id === null) {
                throw ValidationException::withMessages(['status' => 'Only a posted, unpaid payroll run may be reversed.']);
            }
            if (bccomp($run->settlementOpenAmount(), number_format($run->total('net_salary'), 4, '.', ''), 4) !== 0) {
                throw ValidationException::withMessages(['settlement' => 'Reverse posted salary settlements before reversing payroll.']);
            }

            $reversal = $this->reverseJournalEntry->handle($run->journalEntry()->firstOrFail(), $actor, $reversalDate, $reason);
            $this->reverseFinancingRecoveries->handle($run, $reversal, $actor, $reason);
            $run->update([
                'reversal_journal_entry_id' => $reversal->getKey(),
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
            ]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('reversed')
                ->withProperties(['company_id' => $run->company_id, 'reversal_journal_entry_id' => $reversal->getKey(), 'reason' => $reason])
                ->log('reversed payroll posting');

            return $run->refresh();
        }, attempts: 3);
    }
}
