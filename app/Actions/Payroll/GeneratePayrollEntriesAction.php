<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GeneratePayrollEntriesAction
{
    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('generateEntries', $run);

            if (! in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true)) {
                throw ValidationException::withMessages(['payroll_run' => 'Entries can only be generated for draft or rejected payroll.']);
            }

            $employments = Employment::query()
                ->where('company_id', $run->company_id)
                ->whereDate('joining_date', '<=', $run->period_end)
                ->where(fn ($query) => $query->whereNull('ending_date')->orWhereDate('ending_date', '>=', $run->period_start))
                ->with(['employee', 'designation'])
                ->get();

            $snapshots = $employments->map(function (Employment $employment) use ($run): array {
                $compensation = EmploymentCompensation::query()
                    ->where('employment_id', $employment->getKey())
                    ->approved()
                    ->effectiveOn($run->period_end->toDateString())
                    ->latest('effective_from')
                    ->first();

                if ($compensation === null) {
                    throw ValidationException::withMessages([
                        'payroll_run' => "Approved compensation is missing for {$employment->employee->full_name}.",
                    ]);
                }

                $payableFrom = $employment->joining_date->max($run->period_start);
                $payableTo = $employment->ending_date?->min($run->period_end) ?? $run->period_end;
                $periodDays = $run->period_start->diffInDays($run->period_end) + 1;
                $payableDays = $payableFrom->diffInDays($payableTo) + 1;
                $payableBasic = round((float) $compensation->basic_salary * $payableDays / $periodDays, 2);
                $gross = $payableBasic + (float) $compensation->house_travel_allowance
                    + (float) $compensation->food_allowance + (float) $compensation->other_allowance;

                return [
                    'company_id' => $run->company_id,
                    'employment_id' => $employment->getKey(),
                    'employment_compensation_id' => $compensation->getKey(),
                    'employee_name' => $employment->employee->full_name,
                    'employee_code' => $employment->employee_code,
                    'designation' => $employment->designation?->name,
                    'employment_category' => $employment->employment_category->value,
                    'period_days' => $periodDays,
                    'payable_days' => $payableDays,
                    'basic_salary' => $compensation->basic_salary,
                    'payable_basic' => $payableBasic,
                    'house_travel_allowance' => $compensation->house_travel_allowance ?? 0,
                    'food_allowance' => $compensation->food_allowance ?? 0,
                    'other_allowance' => $compensation->other_allowance ?? 0,
                    'gross_salary' => $gross,
                    'absence_deduction' => 0,
                    'loan_advance_deduction' => 0,
                    'other_deduction' => 0,
                    'net_salary' => $gross,
                    'bank_amount' => $gross,
                    'cash_amount' => 0,
                    'currency_code' => $compensation->currency_code,
                ];
            });

            $run->entries()->withTrashed()->forceDelete();
            $snapshots->each(fn (array $snapshot) => $run->entries()->create($snapshot));

            if ($run->status === PayrollRunStatus::Rejected) {
                $run->update([
                    'status' => PayrollRunStatus::Draft,
                    'rejected_by_id' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('generated')
                ->withProperties(['company_id' => $run->company_id, 'entries' => $snapshots->count()])
                ->log('generated payroll entries');

            return $run->load('entries');
        });
    }
}
