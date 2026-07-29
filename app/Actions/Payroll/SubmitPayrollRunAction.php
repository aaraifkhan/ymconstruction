<?php

namespace App\Actions\Payroll;

use App\Enums\EmploymentCategory;
use App\Enums\PayrollComponentType;
use App\Enums\PayrollRunStatus;
use App\Models\EmployeeFinancing;
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
            $run = PayrollRun::query()->with(['calculationRule', 'entries.components'])
                ->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $run);

            if ($run->entries->isEmpty()) {
                throw ValidationException::withMessages(['payroll_run' => 'Generate at least one payroll entry before submission.']);
            }

            foreach ($run->entries as $entry) {
                if ($entry->components->isEmpty()) {
                    throw ValidationException::withMessages([
                        'payroll_run' => "Regenerate Payroll to create source components for {$entry->employee_name}.",
                    ]);
                }
                $entry->assertSourceComponentTotals();
                if (abs(((float) $entry->bank_amount + (float) $entry->cash_amount) - (float) $entry->net_salary) > 0.01) {
                    throw ValidationException::withMessages([
                        'payroll_run' => "Bank and cash allocation must equal net salary for {$entry->employee_name}.",
                    ]);
                }

                foreach ($entry->components->whereIn('type', [
                    PayrollComponentType::LoanInstallment,
                    PayrollComponentType::AdvanceRecovery,
                ]) as $component) {
                    $financing = EmployeeFinancing::query()->whereKey($component->source_id)
                        ->where('company_id', $run->company_id)
                        ->where('employment_id', $entry->employment_id)->first();
                    if ($component->source_type !== EmployeeFinancing::class || $financing === null
                        || bccomp((string) $component->amount, $financing->dueAmountOnOrBefore($run->period_end), 4) === 1) {
                        throw ValidationException::withMessages([
                            'payroll_run' => "Loan/Advance due amount changed for {$entry->employee_name}; regenerate Payroll.",
                        ]);
                    }
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

            $checksum = hash('sha256', json_encode([
                'company_id' => $run->company_id,
                'period_start' => $run->period_start->toDateString(),
                'period_end' => $run->period_end->toDateString(),
                'rule_id' => $run->payroll_calculation_rule_id,
                'rule_updated_at' => $run->calculationRule?->updated_at?->toISOString(),
                'components' => $run->entries->flatMap->components->sortBy('idempotency_key')->map(fn ($component): array => [
                    'key' => $component->idempotency_key,
                    'source_checksum' => $component->source_checksum,
                    'amount' => (string) $component->amount,
                ])->values()->all(),
            ], JSON_THROW_ON_ERROR));
            if (! hash_equals((string) $run->source_checksum, $checksum)) {
                throw ValidationException::withMessages(['payroll_run' => 'Payroll source revision changed; regenerate before submission.']);
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
