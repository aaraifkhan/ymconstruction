<?php

namespace App\Reports;

use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GroupHrReport
{
    /** @return array<string, mixed> */
    public function forGroup(User $actor, Company $root, CarbonInterface $from, CarbonInterface $to): array
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('View:GroupHrReports'))) {
            throw ValidationException::withMessages(['authorization' => 'You are not authorized to view group HR reports.']);
        }

        $companies = $this->groupCompanies($root);
        if ($companies->contains(fn (Company $company): bool => ! $actor->canAccessTenant($company))) {
            throw ValidationException::withMessages(['authorization' => 'Group HR reporting requires access to every company in the selected hierarchy.']);
        }

        $companyIds = $companies->modelKeys();
        $employments = Employment::query()
            ->whereIn('company_id', $companyIds)
            ->get(['id', 'company_id', 'employee_id', 'employment_status', 'joining_date', 'ending_date']);
        $attendance = AttendanceMonthlySummary::query()
            ->whereIn('company_id', $companyIds)
            ->whereDate('period_start', '<=', $to)
            ->whereDate('period_end', '>=', $from)
            ->get(['company_id', 'scheduled_days', 'present_days', 'absent_days', 'half_days', 'leave_days', 'late_minutes']);
        $leave = LeaveRequest::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('starts_on', '<=', $to)
            ->whereDate('ends_on', '>=', $from)
            ->get(['company_id', 'requested_units']);

        $canViewPayroll = $actor->hasRole('super_admin')
            || ($actor->can('View:PayrollReports') && $actor->can('ViewAmounts:PayrollRun'));
        $payroll = $canViewPayroll
            ? PayrollEntry::query()
                ->whereIn('company_id', $companyIds)
                ->whereHas('payrollRun', fn ($query) => $query
                    ->whereDate('period_start', '<=', $to)
                    ->whereDate('period_end', '>=', $from))
                ->get(['company_id', 'gross_salary'])
            : collect();

        $canViewFinancing = $actor->hasRole('super_admin') || $actor->can('ViewAny:EmployeeFinancing');
        $financing = $canViewFinancing
            ? EmployeeFinancing::query()
                ->whereIn('company_id', $companyIds)
                ->with('transactions:id,employee_financing_id,type,total_amount,reversal_of_transaction_id')
                ->get()
            : collect();

        $rows = $companies->map(function (Company $company) use (
            $employments,
            $attendance,
            $leave,
            $payroll,
            $financing,
            $from,
            $to,
            $canViewPayroll,
            $canViewFinancing,
        ): array {
            $companyEmployments = $employments->where('company_id', $company->getKey());
            $companyAttendance = $attendance->where('company_id', $company->getKey());
            $companyFinancing = $financing->where('company_id', $company->getKey());

            return [
                'company' => $company->name,
                'unique_people' => $companyEmployments->pluck('employee_id')->unique()->count(),
                'employment_count' => $companyEmployments->count(),
                'active' => $companyEmployments->whereIn('employment_status', [
                    EmploymentStatus::Active,
                    EmploymentStatus::Probation,
                ])->count(),
                'on_leave' => $companyEmployments->where('employment_status', EmploymentStatus::OnLeave)->count(),
                'joiners' => $companyEmployments->filter(fn (Employment $employment): bool => $employment->joining_date->betweenIncluded($from, $to))->count(),
                'exits' => $companyEmployments->filter(fn (Employment $employment): bool => $employment->ending_date?->betweenIncluded($from, $to) ?? false)->count(),
                'present_days' => $companyAttendance->sum('present_days'),
                'absent_days' => $companyAttendance->sum('absent_days'),
                'half_days' => $companyAttendance->sum('half_days'),
                'late_minutes' => $companyAttendance->sum('late_minutes'),
                'leave_units' => number_format((float) $leave->where('company_id', $company->getKey())->sum('requested_units'), 2, '.', ''),
                'payroll_cost' => $canViewPayroll ? $this->moneySum($payroll->where('company_id', $company->getKey()), 'gross_salary') : null,
                'loan_outstanding' => $canViewFinancing
                    ? $this->financingOutstanding($companyFinancing->where('type', EmployeeFinancingType::Loan))
                    : null,
                'advance_outstanding' => $canViewFinancing
                    ? $this->financingOutstanding($companyFinancing->where('type', EmployeeFinancingType::Advance))
                    : null,
            ];
        });

        return [
            'root' => $root->name,
            'companies' => $companies->pluck('name')->all(),
            'period' => $from->toDateString().' to '.$to->toDateString(),
            'unique_people' => $employments->pluck('employee_id')->unique()->count(),
            'employment_count' => $employments->count(),
            'rows' => $rows,
            'payroll_visible' => $canViewPayroll,
            'financing_visible' => $canViewFinancing,
        ];
    }

    /** @return Collection<int, Company> */
    private function groupCompanies(Company $root): Collection
    {
        return Company::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    private function moneySum(Collection $records, string $field): string
    {
        return $records->reduce(
            fn (string $total, $record): string => bcadd($total, (string) $record->getAttribute($field), 4),
            '0.0000',
        );
    }

    private function financingOutstanding(Collection $records): string
    {
        return $records->reduce(function (string $total, EmployeeFinancing $financing): string {
            $recovered = $financing->transactions
                ->whereIn('type', ['treasury_recovery', 'payroll_recovery', 'waiver'])
                ->sum(fn ($transaction): float => (float) $transaction->total_amount);
            $reversed = $financing->transactions
                ->where('type', 'reversal')
                ->filter(fn ($transaction): bool => $transaction->reversal_of_transaction_id !== null)
                ->sum(fn ($transaction): float => (float) $transaction->total_amount);
            $outstanding = bcsub(
                (string) $financing->total_repayable,
                number_format($recovered - $reversed, 4, '.', ''),
                4,
            );

            return bcadd($total, $outstanding, 4);
        }, '0.0000');
    }
}
