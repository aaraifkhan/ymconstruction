<?php

namespace App\Actions\HR;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use App\Enums\AttendanceRecordState;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\MissingPunchTreatment;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRule;
use App\Models\LeaveRequest;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinalizeAttendanceRecordAction
{
    public function handle(AttendanceRecord $record, User $actor): AttendanceRecord
    {
        return DB::transaction(function () use ($actor, $record): AttendanceRecord {
            $lockedRecord = AttendanceRecord::query()->whereKey($record)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('finalize', $lockedRecord);

            if ($lockedRecord->state === AttendanceRecordState::Finalized) {
                return $lockedRecord;
            }

            $employment = $lockedRecord->employment()->firstOrFail();
            $attendanceDate = CarbonImmutable::parse($lockedRecord->attendance_date);

            if ($attendanceDate->lt($employment->joining_date) || ($employment->ending_date !== null && $attendanceDate->gt($employment->ending_date))) {
                throw ValidationException::withMessages(['attendance_date' => 'Attendance is outside the Employment lifecycle.']);
            }

            $assignment = ShiftAssignment::query()
                ->with(['workCalendar.holidays', 'workShift'])
                ->where('company_id', $lockedRecord->company_id)
                ->where('employment_id', $lockedRecord->employment_id)
                ->whereDate('effective_from', '<=', $attendanceDate)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $attendanceDate))
                ->first();
            $rule = AttendanceRule::query()
                ->where('company_id', $lockedRecord->company_id)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $attendanceDate)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $attendanceDate))
                ->first();

            if ($assignment === null || $rule === null) {
                throw ValidationException::withMessages(['attendance_date' => 'An effective shift assignment and attendance rule are required.']);
            }

            $leave = LeaveRequest::query()
                ->where('company_id', $lockedRecord->company_id)
                ->where('employment_id', $lockedRecord->employment_id)
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('starts_on', '<=', $attendanceDate)
                ->whereDate('ends_on', '>=', $attendanceDate)
                ->first();

            if ($employment->employment_status === EmploymentStatus::OnLeave && $leave === null) {
                throw ValidationException::withMessages([
                    'attendance_date' => 'An Employment marked On Leave requires an approved Leave request covering this date.',
                ]);
            }

            $dayStatus = null;
            if ($leave !== null) {
                $dayStatus = $leave->is_paid_snapshot ? AttendanceDayStatus::PaidLeave : AttendanceDayStatus::UnpaidLeave;
            } elseif (! $assignment->workCalendar->isWorkingDay($attendanceDate)) {
                $hasHoliday = $assignment->workCalendar->holidays->contains(
                    fn ($holiday): bool => $holiday->is_active && $holiday->holiday_date->isSameDay($attendanceDate),
                );
                $dayStatus = $hasHoliday ? AttendanceDayStatus::Holiday : AttendanceDayStatus::RestDay;
            }

            $shiftStart = CarbonImmutable::parse($attendanceDate->toDateString().' '.$assignment->workShift->starts_at);
            $shiftEnd = CarbonImmutable::parse($attendanceDate->toDateString().' '.$assignment->workShift->ends_at);
            if ($assignment->workShift->is_overnight) {
                $shiftEnd = $shiftEnd->addDay();
            }

            $scheduledMinutes = max(0, $shiftStart->diffInMinutes($shiftEnd) - $assignment->workShift->break_minutes);
            $windowStart = $shiftStart->subHours(6);
            $windowEnd = $shiftEnd->addHours(6);
            $punches = AttendancePunch::query()
                ->where('company_id', $lockedRecord->company_id)
                ->where('employment_id', $lockedRecord->employment_id)
                ->where('status', AttendancePunchStatus::Approved)
                ->whereBetween('punched_at', [$windowStart, $windowEnd])
                ->orderBy('punched_at')
                ->get();

            $firstIn = $punches->first(fn (AttendancePunch $punch): bool => $punch->direction === AttendancePunchDirection::In);
            $lastOut = $punches->last(fn (AttendancePunch $punch): bool => $punch->direction === AttendancePunchDirection::Out);
            $workedMinutes = ($firstIn !== null && $lastOut !== null && $lastOut->punched_at->gt($firstIn->punched_at))
                ? max(0, $firstIn->punched_at->diffInMinutes($lastOut->punched_at) - $assignment->workShift->break_minutes)
                : 0;
            $lateMinutes = $firstIn === null ? 0 : max(0, $shiftStart->diffInMinutes($firstIn->punched_at, false) - $rule->grace_minutes);

            if ($lateMinutes > 0 && $rule->late_rounding_minutes > 0) {
                $lateMinutes = (int) (ceil($lateMinutes / $rule->late_rounding_minutes) * $rule->late_rounding_minutes);
            }

            if ($dayStatus === null) {
                if ($firstIn === null || $lastOut === null) {
                    $dayStatus = match ($rule->missing_punch_treatment) {
                        MissingPunchTreatment::Absent => AttendanceDayStatus::Absent,
                        MissingPunchTreatment::HalfDay => AttendanceDayStatus::HalfDay,
                        MissingPunchTreatment::Flag => AttendanceDayStatus::MissingPunch,
                    };
                } elseif ($lateMinutes >= $rule->absence_after_minutes) {
                    $dayStatus = AttendanceDayStatus::Absent;
                } elseif ($lateMinutes >= $rule->half_day_after_minutes) {
                    $dayStatus = AttendanceDayStatus::HalfDay;
                } elseif ($lateMinutes > 0) {
                    $dayStatus = AttendanceDayStatus::Late;
                } else {
                    $dayStatus = AttendanceDayStatus::Present;
                }
            }

            $overtimeMinutes = max(0, $workedMinutes - $scheduledMinutes);
            if ($overtimeMinutes < $rule->minimum_overtime_minutes) {
                $overtimeMinutes = 0;
            }

            $evidence = [
                'assignment_id' => $assignment->getKey(),
                'rule_id' => $rule->getKey(),
                'punch_ids' => $punches->modelKeys(),
                'leave_id' => $leave?->getKey(),
                'day_status' => $dayStatus->value,
            ];

            DB::table('attendance_records')->where('id', $lockedRecord->getKey())->update([
                'shift_assignment_id' => $assignment->getKey(),
                'attendance_rule_id' => $rule->getKey(),
                'day_status' => $dayStatus->value,
                'state' => AttendanceRecordState::Finalized->value,
                'first_in_at' => $firstIn?->punched_at,
                'last_out_at' => $lastOut?->punched_at,
                'scheduled_minutes' => in_array($dayStatus, [AttendanceDayStatus::Holiday, AttendanceDayStatus::RestDay], true) ? 0 : $scheduledMinutes,
                'worked_minutes' => $workedMinutes,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'source_checksum' => hash('sha256', json_encode($evidence, JSON_THROW_ON_ERROR)),
                'finalized_by_id' => $actor->getKey(),
                'finalized_at' => now(),
                'updated_at' => now(),
            ]);

            activity('attendance_records')->causedBy($actor)->performedOn($lockedRecord)->event('finalized')
                ->withProperties(['company_id' => $lockedRecord->company_id, 'evidence' => $evidence])
                ->log('finalized daily attendance');

            return $lockedRecord->refresh();
        }, 3);
    }
}
