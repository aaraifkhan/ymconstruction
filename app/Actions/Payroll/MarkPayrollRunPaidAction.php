<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MarkPayrollRunPaidAction
{
    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('markPaid', $run);
            if (! $run->isPostedToAccounts()) {
                throw ValidationException::withMessages(['payroll_run' => 'Post payroll to Accounts before marking it paid.']);
            }
            if (bccomp($run->settlementOpenAmount(), '0', 4) !== 0) {
                throw ValidationException::withMessages(['payroll_run' => 'Every payroll entry must be fully settled through posted Treasury payments.']);
            }
            $run->update(['status' => PayrollRunStatus::Paid, 'paid_by_id' => $actor->getKey(), 'paid_at' => now()]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('paid')->withProperties(['company_id' => $run->company_id])->log('marked payroll run paid');

            return $run;
        });
    }
}
