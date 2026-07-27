<?php

namespace App\Actions\Payroll;

use App\Enums\EmploymentCategory;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitPayrollRunAction
{
    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->with('entries')->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $run);

            if ($run->entries->isEmpty()) {
                throw ValidationException::withMessages(['payroll_run' => 'Generate at least one payroll entry before submission.']);
            }

            foreach ($run->entries as $entry) {
                if (abs(((float) $entry->bank_amount + (float) $entry->cash_amount) - (float) $entry->net_salary) > 0.01) {
                    throw ValidationException::withMessages([
                        'payroll_run' => "Bank and cash allocation must equal net salary for {$entry->employee_name}.",
                    ]);
                }

                if ($entry->employment_category === EmploymentCategory::ProjectStaff->value) {
                    $allocated = (string) $entry->projectAllocations()->sum('amount');
                    if (bccomp($allocated, $entry->expenseBasis(), 4) !== 0) {
                        throw ValidationException::withMessages([
                            'payroll_run' => "Project allocations must equal payroll expense for {$entry->employee_name}.",
                        ]);
                    }
                }
            }

            $run->update([
                'status' => PayrollRunStatus::UnderReview,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('submitted')
                ->withProperties(['company_id' => $run->company_id])->log('submitted payroll run');

            return $run;
        });
    }
}
