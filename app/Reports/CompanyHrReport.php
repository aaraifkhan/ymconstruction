<?php

namespace App\Reports;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompanyHrReport
{
    /** @return array<string, mixed> */
    public function forCompany(User $actor, Company $company): array
    {
        $this->authorize($actor, $company);

        $employments = Employment::query()
            ->whereBelongsTo($company)
            ->with([
                'employee:id,full_name',
                'department:id,name',
                'designation:id,name',
                'workLocation:id,name',
            ])
            ->orderBy('employee_code')
            ->get();

        $employeeRows = $employments->map(fn (Employment $employment): array => [
            'employee_id' => $employment->employee_id,
            'employee_code' => $employment->employee_code,
            'employee_name' => $employment->employee->full_name,
            'department' => $employment->department?->name ?? 'Unassigned',
            'designation' => $employment->designation?->name ?? 'Unassigned',
            'employment_type' => $employment->employment_type->label(),
            'employment_status' => $employment->employment_status->label(),
            'work_location' => $employment->workLocation?->name ?? 'Unassigned',
            'joining_date' => $employment->joining_date->toDateString(),
            'ending_date' => $employment->ending_date?->toDateString(),
        ]);

        return [
            'dashboard' => $this->dashboard($company),
            'employees' => $employeeRows,
            'departments' => $employeeRows->sortBy([
                ['department', 'asc'],
                ['employee_code', 'asc'],
            ])->values(),
            'designations' => $employeeRows->sortBy([
                ['designation', 'asc'],
                ['employee_code', 'asc'],
            ])->values(),
            'financing' => $actor->hasRole('super_admin') || $actor->can('ViewAny:EmployeeFinancing')
                ? $this->financing($company)
                : collect(),
            'increments' => $actor->hasRole('super_admin') || $actor->can('ViewAmounts:EmploymentCompensation')
                ? $this->increments($company)
                : collect(),
            'attendance' => $actor->hasRole('super_admin') || $actor->can('ViewAny:AttendanceMonthlySummary')
                ? $this->attendance($company)
                : collect(),
            'leave' => $actor->hasRole('super_admin') || $actor->can('ViewAny:LeaveRequest')
                ? $this->leave($company)
                : collect(),
            'can_view_financing' => $actor->hasRole('super_admin') || $actor->can('ViewAny:EmployeeFinancing'),
            'can_view_increments' => $actor->hasRole('super_admin') || $actor->can('ViewAmounts:EmploymentCompensation'),
            'can_view_attendance' => $actor->hasRole('super_admin') || $actor->can('ViewAny:AttendanceMonthlySummary'),
            'can_view_leave' => $actor->hasRole('super_admin') || $actor->can('ViewAny:LeaveRequest'),
        ];
    }

    /** @return array<string, int> */
    private function dashboard(Company $company): array
    {
        $today = today();
        $employment = Employment::query()
            ->whereBelongsTo($company)
            ->selectRaw('COUNT(*) AS employment_count')
            ->selectRaw('COUNT(DISTINCT employee_id) AS unique_people')
            ->selectRaw('SUM(CASE WHEN employment_status IN (?, ?) THEN 1 ELSE 0 END) AS active_count', [
                EmploymentStatus::Active->value,
                EmploymentStatus::Probation->value,
            ])
            ->selectRaw('SUM(CASE WHEN employment_status = ? THEN 1 ELSE 0 END) AS on_leave_count', [
                EmploymentStatus::OnLeave->value,
            ])
            ->firstOrFail();

        return [
            'unique_people' => (int) $employment->unique_people,
            'employment_count' => (int) $employment->employment_count,
            'active_count' => (int) $employment->active_count,
            'on_leave_count' => (int) $employment->on_leave_count,
            'joiners_this_month' => Employment::query()->whereBelongsTo($company)
                ->whereBetween('joining_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->count(),
            'exits_this_month' => Employment::query()->whereBelongsTo($company)
                ->whereBetween('ending_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->count(),
            'pending_leave_requests' => LeaveRequest::query()->whereBelongsTo($company)
                ->whereIn('status', [
                    LeaveRequestStatus::Requested->value,
                    LeaveRequestStatus::ManagerApproved->value,
                ])->count(),
            'attendance_exceptions' => AttendanceMonthlySummary::query()->whereBelongsTo($company)
                ->whereDate('period_start', '>=', $today->copy()->startOfMonth())
                ->where(fn ($query) => $query
                    ->where('absent_days', '>', 0)
                    ->orWhere('half_days', '>', 0)
                    ->orWhere('late_minutes', '>', 0))
                ->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function financing(Company $company): Collection
    {
        return EmployeeFinancing::query()
            ->whereBelongsTo($company)
            ->with([
                'employment:id,employee_id,employee_code',
                'employment.employee:id,full_name',
                'transactions:id,employee_financing_id,type,total_amount,reversal_of_transaction_id',
            ])
            ->orderByDesc('request_date')
            ->get()
            ->map(function (EmployeeFinancing $financing): array {
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

                return [
                    'type' => $financing->type->label(),
                    'reference_number' => $financing->reference_number ?? 'Draft',
                    'employee_code' => $financing->employment->employee_code,
                    'employee_name' => $financing->employment->employee->full_name,
                    'request_date' => $financing->request_date->toDateString(),
                    'status' => $financing->status->label(),
                    'principal' => (string) $financing->principal_amount,
                    'total_repayable' => (string) $financing->total_repayable,
                    'outstanding' => $outstanding,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function increments(Company $company): Collection
    {
        return EmploymentCompensation::query()
            ->approved()
            ->whereBelongsTo($company)
            ->with(['employment:id,employee_id,employee_code', 'employment.employee:id,full_name'])
            ->orderBy('employment_id')
            ->orderBy('effective_from')
            ->get()
            ->groupBy('employment_id')
            ->flatMap(function (Collection $records): Collection {
                $previousGross = null;

                return $records->map(function (EmploymentCompensation $compensation) use (&$previousGross): array {
                    $gross = number_format($compensation->grossSalary(), 4, '.', '');
                    $row = [
                        'employee_code' => $compensation->employment->employee_code,
                        'employee_name' => $compensation->employment->employee->full_name,
                        'effective_from' => $compensation->effective_from->toDateString(),
                        'effective_to' => $compensation->effective_to?->toDateString(),
                        'previous_gross' => $previousGross,
                        'new_gross' => $gross,
                        'increment' => $previousGross === null ? null : bcsub($gross, $previousGross, 4),
                    ];
                    $previousGross = $gross;

                    return $row;
                });
            })
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function attendance(Company $company): Collection
    {
        return AttendanceMonthlySummary::query()
            ->whereBelongsTo($company)
            ->with(['employment:id,employee_id,employee_code', 'employment.employee:id,full_name'])
            ->orderByDesc('period_end')
            ->orderBy('employment_id')
            ->get()
            ->map(fn (AttendanceMonthlySummary $summary): array => [
                'period' => $summary->period_start->toDateString().' to '.$summary->period_end->toDateString(),
                'employee_code' => $summary->employment->employee_code,
                'employee_name' => $summary->employment->employee->full_name,
                'status' => str($summary->status->value)->headline()->toString(),
                'scheduled_days' => $summary->scheduled_days,
                'present_days' => $summary->present_days,
                'absent_days' => $summary->absent_days,
                'half_days' => $summary->half_days,
                'leave_days' => $summary->leave_days,
                'late_minutes' => $summary->late_minutes,
                'overtime_minutes' => $summary->overtime_minutes,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function leave(Company $company): Collection
    {
        $balances = LeaveLedgerEntry::query()
            ->whereBelongsTo($company)
            ->with([
                'employment:id,employee_id,employee_code',
                'employment.employee:id,full_name',
                'leaveType:id,name',
            ])
            ->get()
            ->groupBy(fn (LeaveLedgerEntry $entry): string => "{$entry->employment_id}:{$entry->leave_type_id}")
            ->map(fn (Collection $entries): string => number_format(
                $entries->sum(fn (LeaveLedgerEntry $entry): float => (float) $entry->units),
                2,
                '.',
                '',
            ));

        return LeaveRequest::query()
            ->whereBelongsTo($company)
            ->with([
                'employment:id,employee_id,employee_code',
                'employment.employee:id,full_name',
                'leaveType:id,name',
            ])
            ->orderByDesc('starts_on')
            ->get()
            ->map(fn (LeaveRequest $request): array => [
                'employee_code' => $request->employment->employee_code,
                'employee_name' => $request->employment->employee->full_name,
                'leave_type' => $request->leaveType->name,
                'dates' => $request->starts_on->toDateString().' to '.$request->ends_on->toDateString(),
                'requested_units' => (string) $request->requested_units,
                'status' => str($request->status->value)->headline()->toString(),
                'paid' => $request->is_paid_snapshot ? 'Yes' : 'No',
                'current_balance' => $balances->get("{$request->employment_id}:{$request->leave_type_id}", '0.00'),
            ]);
    }

    private function authorize(User $actor, Company $company): void
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('View:HrReports')) || ! $actor->canAccessTenant($company)) {
            throw ValidationException::withMessages(['authorization' => 'You are not authorized to view this company HR report.']);
        }
    }
}
