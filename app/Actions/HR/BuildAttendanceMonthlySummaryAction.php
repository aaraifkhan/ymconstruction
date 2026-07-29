<?php

namespace App\Actions\HR;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceRecordState;
use App\Enums\AttendanceSummaryStatus;
use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\AttendanceRecord;
use App\Models\Employment;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BuildAttendanceMonthlySummaryAction
{
    public function handle(Employment $employment, CarbonInterface $periodStart, CarbonInterface $periodEnd, User $actor): AttendanceMonthlySummary
    {
        if ($periodEnd->lt($periodStart)) {
            throw ValidationException::withMessages(['period_end' => 'The period end must be on or after the start.']);
        }

        return DB::transaction(function () use ($actor, $employment, $periodEnd, $periodStart): AttendanceMonthlySummary {
            $summary = AttendanceMonthlySummary::query()->firstOrNew([
                'company_id' => $employment->company_id,
                'employment_id' => $employment->getKey(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);
            Gate::forUser($actor)->authorize('generate', $summary);

            if ($summary->exists && $summary->status === AttendanceSummaryStatus::Finalized) {
                throw ValidationException::withMessages(['status' => 'A finalized summary cannot be rebuilt.']);
            }

            $records = AttendanceRecord::query()
                ->where('company_id', $employment->company_id)
                ->where('employment_id', $employment->getKey())
                ->whereBetween('attendance_date', [$periodStart, $periodEnd])
                ->where('state', AttendanceRecordState::Finalized)
                ->orderBy('attendance_date')
                ->get();
            $unpaidLeaveUnits = LeaveRequest::query()
                ->where('company_id', $employment->company_id)
                ->where('employment_id', $employment->getKey())
                ->where('status', LeaveRequestStatus::Approved)
                ->where('payroll_impact_snapshot', LeavePayrollImpact::UnpaidDeduction)
                ->whereDate('starts_on', '<=', $periodEnd)
                ->whereDate('ends_on', '>=', $periodStart)
                ->sum('requested_units');

            $payload = [
                'company_id' => $employment->company_id,
                'employment_id' => $employment->getKey(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => AttendanceSummaryStatus::Draft,
                'scheduled_days' => $records->where('scheduled_minutes', '>', 0)->count(),
                'scheduled_minutes' => $records->sum('scheduled_minutes'),
                'present_days' => $records->where('day_status', AttendanceDayStatus::Present)->count(),
                'absent_days' => $records->where('day_status', AttendanceDayStatus::Absent)->count(),
                'half_days' => $records->where('day_status', AttendanceDayStatus::HalfDay)->count(),
                'leave_days' => $records->whereIn('day_status', [AttendanceDayStatus::PaidLeave, AttendanceDayStatus::UnpaidLeave])->count(),
                'late_minutes' => $records->sum('late_minutes'),
                'overtime_minutes' => $records->sum('overtime_minutes'),
                'unpaid_leave_units' => $unpaidLeaveUnits,
                'unpaid_leave_days' => $records->where('day_status', AttendanceDayStatus::UnpaidLeave)->count(),
                'source_checksum' => hash('sha256', json_encode([
                    'records' => $records->map->only(['id', 'source_checksum'])->all(),
                    'unpaid_leave_units' => $unpaidLeaveUnits,
                ], JSON_THROW_ON_ERROR)),
            ];

            $summary->fill($payload)->save();

            return $summary->refresh();
        }, 3);
    }
}
