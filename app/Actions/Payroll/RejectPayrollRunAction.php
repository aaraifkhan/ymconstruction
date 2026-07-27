<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectPayrollRunAction
{
    public function handle(PayrollRun $payrollRun, User $actor, string $reason): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun, $reason): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('reject', $run);
            if (blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
            }
            $run->update(['status' => PayrollRunStatus::Rejected, 'rejected_by_id' => $actor->getKey(), 'rejected_at' => now(), 'rejection_reason' => $reason]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('rejected')->withProperties(['company_id' => $run->company_id, 'reason' => $reason])->log('rejected payroll run');

            return $run;
        });
    }
}
