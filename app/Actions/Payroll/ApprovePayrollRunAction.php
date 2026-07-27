<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApprovePayrollRunAction
{
    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('approve', $run);
            $run->update(['status' => PayrollRunStatus::Approved, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('approved')->withProperties(['company_id' => $run->company_id])->log('approved payroll run');

            return $run;
        });
    }
}
