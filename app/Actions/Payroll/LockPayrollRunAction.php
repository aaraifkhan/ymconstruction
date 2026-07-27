<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LockPayrollRunAction
{
    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('lock', $run);
            $run->update(['status' => PayrollRunStatus::Locked, 'locked_by_id' => $actor->getKey(), 'locked_at' => now()]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('locked')->withProperties(['company_id' => $run->company_id])->log('locked payroll run');

            return $run;
        });
    }
}
