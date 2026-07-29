<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\PayrollProjectAllocation;
use Illuminate\Support\Collection;

class ProjectPayrollReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return PayrollProjectAllocation::query()
            ->whereBelongsTo($company)
            ->with([
                'payrollEntry:id,payroll_run_id,employee_name,employee_code',
                'payrollEntry.payrollRun:id,reference_number,period_start,period_end,status',
                'project:id,name',
                'projectSite:id,name',
                'costCenter:id,name',
            ])
            ->latest('payroll_entry_id')
            ->get()
            ->map(fn (PayrollProjectAllocation $allocation): array => [
                'allocation' => $allocation,
                'entry' => $allocation->payrollEntry,
                'run' => $allocation->payrollEntry->payrollRun,
                'project' => $allocation->project,
                'site' => $allocation->projectSite,
                'cost_center' => $allocation->costCenter,
                'amount' => (string) $allocation->amount,
            ]);
    }
}
