<?php

namespace App\Actions\Payroll;

use App\Enums\AttendanceSummaryStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\PayrollAccountComponent;
use App\Enums\PayrollComponentNature;
use App\Enums\PayrollComponentType;
use App\Enums\PayrollVariableComponentType;
use App\Models\AttendanceMonthlySummary;
use App\Models\EmployeeFinancing;
use App\Models\EmploymentCompensation;
use App\Models\PayrollCalculationRule;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryComponent;
use App\Models\PayrollVariableComponent;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BuildPayrollEntryComponentsAction
{
    /**
     * @param  Collection<int, PayrollVariableComponent>  $variableComponents
     * @param  Collection<int, EmployeeFinancing>  $financings
     */
    public function handle(
        PayrollEntry $entry,
        EmploymentCompensation $compensation,
        ?PayrollCalculationRule $rule,
        ?AttendanceMonthlySummary $attendance,
        Collection $variableComponents,
        Collection $financings,
    ): PayrollEntry {
        if ($rule?->requires_finalized_attendance
            && ($attendance === null || $attendance->status !== AttendanceSummaryStatus::Finalized)) {
            throw ValidationException::withMessages([
                'attendance' => "A matching finalized Attendance summary is required for {$entry->employee_name}.",
            ]);
        }

        $periodDays = (string) $entry->period_days;
        $payableDays = (string) $entry->payable_days;
        $basicSalary = $this->money($compensation->basic_salary);
        $payableBasic = $this->multiplyAndDivide($basicSalary, $payableDays, $periodDays);
        $allowanceFactor = $rule?->prorate_allowances ? bcdiv($payableDays, $periodDays, 8) : '1.00000000';
        $houseTravel = $this->money(bcmul((string) ($compensation->house_travel_allowance ?? 0), $allowanceFactor, 8));
        $food = $this->money(bcmul((string) ($compensation->food_allowance ?? 0), $allowanceFactor, 8));
        $otherAllowance = $this->money(bcmul((string) ($compensation->other_allowance ?? 0), $allowanceFactor, 8));
        $compensationChecksum = hash('sha256', json_encode([
            'id' => $compensation->getKey(),
            'effective_from' => $compensation->effective_from->toDateString(),
            'effective_to' => $compensation->effective_to?->toDateString(),
            'basic' => $basicSalary,
            'house_travel' => $houseTravel,
            'food' => $food,
            'other' => $otherAllowance,
        ], JSON_THROW_ON_ERROR));

        $this->create($entry, PayrollComponentType::PayableBasic, PayrollComponentNature::Earning,
            $payableDays, bcdiv($basicSalary, $periodDays, 8), $payableBasic,
            $compensation, $compensationChecksum, ['compensation_id' => $compensation->getKey(), 'period_days' => $entry->period_days],
            PayrollAccountComponent::BasicSalary);
        $this->create($entry, PayrollComponentType::HouseTravelAllowance, PayrollComponentNature::Earning,
            '1', $houseTravel, $houseTravel, $compensation, $compensationChecksum,
            ['compensation_id' => $compensation->getKey(), 'prorated' => (bool) $rule?->prorate_allowances],
            PayrollAccountComponent::HouseTravelAllowance);
        $this->create($entry, PayrollComponentType::FoodAllowance, PayrollComponentNature::Earning,
            '1', $food, $food, $compensation, $compensationChecksum,
            ['compensation_id' => $compensation->getKey(), 'prorated' => (bool) $rule?->prorate_allowances],
            PayrollAccountComponent::FoodAllowance);
        $this->create($entry, PayrollComponentType::OtherAllowance, PayrollComponentNature::Earning,
            '1', $otherAllowance, $otherAllowance, $compensation, $compensationChecksum,
            ['compensation_id' => $compensation->getKey(), 'prorated' => (bool) $rule?->prorate_allowances],
            PayrollAccountComponent::OtherAllowance);

        $bonus = '0.0000';
        $incentive = '0.0000';
        foreach ($variableComponents as $variable) {
            $type = $variable->type === PayrollVariableComponentType::Bonus
                ? PayrollComponentType::Bonus
                : PayrollComponentType::Incentive;
            $accountComponent = $variable->type === PayrollVariableComponentType::Bonus
                ? PayrollAccountComponent::Bonus
                : PayrollAccountComponent::Incentive;
            $amount = $this->money($variable->amount);
            $this->create($entry, $type, PayrollComponentNature::Earning, '1', $amount, $amount,
                $variable, $variable->source_checksum, [
                    'source_reference' => $variable->source_reference,
                    'earning_period_start' => $variable->earning_period_start->toDateString(),
                    'earning_period_end' => $variable->earning_period_end->toDateString(),
                    'project_id' => $variable->project_id,
                    'approved_by_id' => $variable->approved_by_id,
                    'approved_at' => $variable->approved_at?->toISOString(),
                ], $accountComponent);
            if ($type === PayrollComponentType::Bonus) {
                $bonus = bcadd($bonus, $amount, 4);
            } else {
                $incentive = bcadd($incentive, $amount, 4);
            }
        }

        $dailyRate = bcdiv($basicSalary, $periodDays, 8);
        $absence = '0.0000';
        $unpaidLeave = '0.0000';
        $late = '0.0000';
        $halfDay = '0.0000';
        if ($attendance !== null && $rule !== null) {
            $evidence = [
                'summary_id' => $attendance->getKey(),
                'summary_checksum' => $attendance->source_checksum,
                'rule_id' => $rule->getKey(),
                'rule_effective_from' => $rule->effective_from->toDateString(),
            ];
            $absence = $this->attendanceDeduction(
                $entry, PayrollComponentType::AbsenceDeduction, PayrollAccountComponent::AbsenceDeduction,
                (string) $attendance->absent_days, $dailyRate, $rule->absence_day_factor,
                $attendance, $evidence,
            );
            $unpaidLeave = $this->attendanceDeduction(
                $entry, PayrollComponentType::UnpaidLeaveDeduction, PayrollAccountComponent::UnpaidLeaveDeduction,
                (string) $attendance->unpaid_leave_days, $dailyRate, $rule->unpaid_leave_day_factor,
                $attendance, $evidence,
            );
            $halfDay = $this->attendanceDeduction(
                $entry, PayrollComponentType::HalfDayDeduction, PayrollAccountComponent::HalfDayDeduction,
                (string) $attendance->half_days, $dailyRate, $rule->half_day_factor,
                $attendance, $evidence,
            );
            if ($rule->deduct_late_minutes && $attendance->late_minutes > 0) {
                $minuteRate = bcdiv($dailyRate, (string) $rule->standard_day_minutes, 8);
                $late = $this->money(bcmul((string) $attendance->late_minutes, $minuteRate, 8));
                $this->create(
                    $entry, PayrollComponentType::LateDeduction, PayrollComponentNature::Deduction,
                    (string) $attendance->late_minutes, $minuteRate, $late,
                    $attendance, $attendance->source_checksum, $evidence,
                    PayrollAccountComponent::LateDeduction,
                );
            }
        }

        $gross = collect([$payableBasic, $houseTravel, $food, $otherAllowance, $bonus, $incentive])
            ->reduce(fn (string $total, string $amount): string => bcadd($total, $amount, 4), '0.0000');
        $attendanceDeductions = collect([$absence, $unpaidLeave, $late, $halfDay])
            ->reduce(fn (string $total, string $amount): string => bcadd($total, $amount, 4), '0.0000');
        $availableForFinancing = max(0, (float) bcsub($gross, $attendanceDeductions, 4));
        $financingRecovery = '0.0000';
        foreach ($financings->sortBy('id') as $financing) {
            if ($availableForFinancing <= 0) {
                break;
            }
            $due = $financing->installments
                ->where('due_date', '<=', $entry->payrollRun->period_end)
                ->reject(fn ($installment) => $installment->status->value === 'superseded')
                ->reduce(fn (string $total, $installment): string => bcadd(
                    $total,
                    $installment->outstandingAmount(),
                    4,
                ), '0.0000');
            $amount = $this->money(min((float) $due, $availableForFinancing));
            if (bccomp($amount, '0', 4) !== 1) {
                continue;
            }
            $type = $financing->type === EmployeeFinancingType::Loan
                ? PayrollComponentType::LoanInstallment
                : PayrollComponentType::AdvanceRecovery;
            $this->create($entry, $type, PayrollComponentNature::Deduction, '1', $amount, $amount,
                $financing, hash('sha256', json_encode([
                    'financing_id' => $financing->getKey(),
                    'reference' => $financing->reference_number,
                    'due_as_of' => $entry->payrollRun->period_end->toDateString(),
                    'due_amount' => $due,
                ], JSON_THROW_ON_ERROR)), [
                    'financing_id' => $financing->getKey(),
                    'reference_number' => $financing->reference_number,
                    'due_as_of' => $entry->payrollRun->period_end->toDateString(),
                    'due_amount' => $due,
                ]);
            $financingRecovery = bcadd($financingRecovery, $amount, 4);
            $availableForFinancing -= (float) $amount;
        }

        $entry->update([
            'payable_basic' => $payableBasic,
            'house_travel_allowance' => $houseTravel,
            'food_allowance' => $food,
            'other_allowance' => $otherAllowance,
            'bonus_amount' => $bonus,
            'incentive_amount' => $incentive,
            'absence_deduction' => $absence,
            'unpaid_leave_deduction' => $unpaidLeave,
            'late_deduction' => $late,
            'half_day_deduction' => $halfDay,
            'loan_advance_deduction' => $financingRecovery,
            'other_deduction' => 0,
            'bank_amount' => max(0, (float) bcsub(bcsub($gross, $attendanceDeductions, 4), $financingRecovery, 4)),
            'cash_amount' => 0,
        ]);

        return $entry->refresh()->load('components');
    }

    private function attendanceDeduction(
        PayrollEntry $entry,
        PayrollComponentType $type,
        PayrollAccountComponent $accountComponent,
        string $quantity,
        string $dailyRate,
        ?string $factor,
        AttendanceMonthlySummary $attendance,
        array $evidence,
    ): string {
        if ($factor === null || bccomp($quantity, '0', 4) !== 1 || bccomp($factor, '0', 4) !== 1) {
            return '0.0000';
        }
        $rate = bcmul($dailyRate, $factor, 8);
        $amount = $this->money(bcmul($quantity, $rate, 8));
        $this->create($entry, $type, PayrollComponentNature::Deduction, $quantity, $rate, $amount,
            $attendance, $attendance->source_checksum, $evidence, $accountComponent);

        return $amount;
    }

    private function create(
        PayrollEntry $entry,
        PayrollComponentType $type,
        PayrollComponentNature $nature,
        string $quantity,
        string $rate,
        string $amount,
        object $source,
        string $sourceChecksum,
        array $evidence,
        ?PayrollAccountComponent $accountComponent = null,
    ): ?PayrollEntryComponent {
        if (bccomp($amount, '0', 4) !== 1) {
            return null;
        }
        $sourceId = method_exists($source, 'getKey') ? $source->getKey() : null;
        $idempotencyKey = implode(':', [
            'payroll', $entry->payroll_run_id, 'employment', $entry->employment_id,
            $type->value, $source::class, $sourceId ?? 'none',
        ]);

        return $entry->components()->create([
            'company_id' => $entry->company_id,
            'employment_id' => $entry->employment_id,
            'type' => $type,
            'nature' => $nature,
            'source_type' => $source::class,
            'source_id' => $sourceId,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
            'account_component' => $accountComponent,
            'source_checksum' => $sourceChecksum,
            'evidence_snapshot' => $evidence,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private function multiplyAndDivide(string $amount, string $multiplier, string $divisor): string
    {
        return $this->money(bcdiv(bcmul($amount, $multiplier, 8), $divisor, 8));
    }

    private function money(string|int|float|null $amount): string
    {
        return number_format(round((float) ($amount ?? 0), 2), 4, '.', '');
    }
}
