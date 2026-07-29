<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\PayrollEntry;
use Illuminate\Support\Collection;

class SalaryRegisterReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return PayrollEntry::query()
            ->whereBelongsTo($company)
            ->with('payrollRun:id,reference_number,period_start,period_end,status')
            ->latest('payroll_run_id')
            ->orderBy('employee_code')
            ->get()
            ->map(fn (PayrollEntry $entry): array => [
                'entry' => $entry,
                'run' => $entry->payrollRun,
                'gross' => (string) $entry->gross_salary,
                'attendance_deductions' => collect([
                    'absence_deduction', 'unpaid_leave_deduction', 'late_deduction', 'half_day_deduction',
                ])->reduce(fn (string $total, string $field): string => bcadd(
                    $total,
                    (string) ($entry->getAttribute($field) ?? 0),
                    4,
                ), '0.0000'),
                'financing_recovery' => (string) $entry->loan_advance_deduction,
                'other_deduction' => (string) $entry->other_deduction,
                'net' => (string) $entry->net_salary,
            ]);
    }
}
