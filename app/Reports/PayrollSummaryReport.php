<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;

class PayrollSummaryReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return PayrollRun::query()
            ->whereBelongsTo($company)
            ->with('entries')
            ->orderByDesc('period_end')
            ->get()
            ->map(fn (PayrollRun $run): array => [
                'run' => $run,
                'employees' => $run->entries->count(),
                'gross' => number_format($run->total('gross_salary'), 4, '.', ''),
                'bonus' => number_format($run->total('bonus_amount'), 4, '.', ''),
                'incentive' => number_format($run->total('incentive_amount'), 4, '.', ''),
                'attendance_deductions' => number_format(
                    collect(['absence_deduction', 'unpaid_leave_deduction', 'late_deduction', 'half_day_deduction'])
                        ->sum(fn (string $field): float => $run->total($field)),
                    4,
                    '.',
                    '',
                ),
                'financing_recovery' => number_format($run->total('loan_advance_deduction'), 4, '.', ''),
                'net' => number_format($run->total('net_salary'), 4, '.', ''),
            ]);
    }
}
