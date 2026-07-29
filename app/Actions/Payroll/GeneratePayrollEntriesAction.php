<?php

namespace App\Actions\Payroll;

use App\Enums\EmployeeFinancingStatus;
use App\Enums\PayrollRunStatus;
use App\Enums\PayrollVariableComponentStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\PayrollCalculationRule;
use App\Models\PayrollRun;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GeneratePayrollEntriesAction
{
    public function __construct(private BuildPayrollEntryComponentsAction $buildComponents) {}

    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('generateEntries', $run);

            if (! in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true)) {
                throw ValidationException::withMessages(['payroll_run' => 'Entries can only be generated for draft or rejected payroll.']);
            }

            $rule = PayrollCalculationRule::query()->where('company_id', $run->company_id)
                ->effectiveOn($run->period_end->toDateString())->latest('effective_from')->first();
            $employments = Employment::query()
                ->where('company_id', $run->company_id)
                ->whereDate('joining_date', '<=', $run->period_end)
                ->where(fn (Builder $query) => $query->whereNull('ending_date')
                    ->orWhereDate('ending_date', '>=', $run->period_start))
                ->with([
                    'employee',
                    'designation',
                    'compensations' => fn ($query) => $query->approved()
                        ->effectiveOn($run->period_end->toDateString())->latest('effective_from'),
                ])->orderBy('id')->get();

            $run->entries()->withTrashed()->forceDelete();
            $employmentIds = $employments->modelKeys();
            $summaries = AttendanceMonthlySummary::query()->where('company_id', $run->company_id)
                ->whereIn('employment_id', $employmentIds)
                ->whereDate('period_start', $run->period_start)
                ->whereDate('period_end', $run->period_end)->get()->keyBy('employment_id');
            $variables = PayrollVariableComponent::query()->where('company_id', $run->company_id)
                ->whereIn('employment_id', $employmentIds)
                ->where('status', PayrollVariableComponentStatus::Approved)
                ->whereDate('earning_period_start', '<=', $run->period_end)
                ->whereDate('earning_period_end', '>=', $run->period_start)
                ->whereDoesntHave('payrollEntryComponents', fn (Builder $query) => $query
                    ->whereHas('payrollEntry.payrollRun', fn (Builder $query) => $query
                        ->whereNotIn('status', [PayrollRunStatus::Draft, PayrollRunStatus::Rejected])))
                ->orderBy('id')->get()->groupBy('employment_id');
            $financings = EmployeeFinancing::query()->where('company_id', $run->company_id)
                ->whereIn('employment_id', $employmentIds)
                ->where('status', EmployeeFinancingStatus::Active)
                ->with(['installments' => fn ($query) => $query->orderBy('due_date')->orderBy('installment_number')])
                ->orderBy('id')->get()->groupBy('employment_id');

            foreach ($employments as $employment) {
                $compensation = $employment->compensations->first();
                if ($compensation === null) {
                    throw ValidationException::withMessages([
                        'payroll_run' => "Approved compensation is missing for {$employment->employee->full_name}.",
                    ]);
                }

                $payableFrom = $employment->joining_date->max($run->period_start);
                $payableTo = $employment->ending_date?->min($run->period_end) ?? $run->period_end;
                $periodDays = $run->period_start->diffInDays($run->period_end) + 1;
                $payableDays = $payableFrom->diffInDays($payableTo) + 1;
                $entry = $run->entries()->create([
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
                    'payable_basic' => 0,
                    'house_travel_allowance' => 0,
                    'food_allowance' => 0,
                    'other_allowance' => 0,
                    'bonus_amount' => 0,
                    'incentive_amount' => 0,
                    'gross_salary' => 0,
                    'absence_deduction' => 0,
                    'unpaid_leave_deduction' => 0,
                    'late_deduction' => 0,
                    'half_day_deduction' => 0,
                    'loan_advance_deduction' => 0,
                    'other_deduction' => 0,
                    'net_salary' => 0,
                    'bank_amount' => 0,
                    'cash_amount' => 0,
                    'currency_code' => $compensation->currency_code,
                ]);
                $this->buildComponents->handle(
                    $entry,
                    $compensation,
                    $rule,
                    $summaries->get($employment->getKey()),
                    $variables->get($employment->getKey(), collect()),
                    $financings->get($employment->getKey(), collect()),
                );
            }

            $components = $run->entries()->with('components')->orderBy('employment_id')->get()
                ->flatMap->components->sortBy('idempotency_key')->map(fn ($component): array => [
                    'key' => $component->idempotency_key,
                    'source_checksum' => $component->source_checksum,
                    'amount' => (string) $component->amount,
                ])->values()->all();
            $run->update([
                'status' => PayrollRunStatus::Draft,
                'payroll_calculation_rule_id' => $rule?->getKey(),
                'generation_revision' => $run->generation_revision + 1,
                'source_checksum' => hash('sha256', json_encode([
                    'company_id' => $run->company_id,
                    'period_start' => $run->period_start->toDateString(),
                    'period_end' => $run->period_end->toDateString(),
                    'rule_id' => $rule?->getKey(),
                    'rule_updated_at' => $rule?->updated_at?->toISOString(),
                    'components' => $components,
                ], JSON_THROW_ON_ERROR)),
                'generated_by_id' => $actor->getKey(),
                'generated_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('generated')
                ->withProperties([
                    'company_id' => $run->company_id,
                    'entries' => $employments->count(),
                    'generation_revision' => $run->generation_revision,
                    'source_checksum' => $run->source_checksum,
                ])->log('generated traceable payroll entries');

            return $run->load('entries.components');
        }, 3);
    }
}
