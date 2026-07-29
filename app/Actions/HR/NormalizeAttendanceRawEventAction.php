<?php

namespace App\Actions\HR;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendancePunchSource;
use App\Enums\AttendancePunchStatus;
use App\Enums\AttendanceRawEventStatus;
use App\Enums\AttendanceRecordState;
use App\Models\AttendanceDeviceUserMapping;
use App\Models\AttendancePunch;
use App\Models\AttendanceRawEvent;
use App\Models\AttendanceRecord;
use App\Models\ShiftAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class NormalizeAttendanceRawEventAction
{
    public function handle(AttendanceRawEvent $rawEvent): AttendanceRawEvent
    {
        return DB::transaction(function () use ($rawEvent): AttendanceRawEvent {
            $event = AttendanceRawEvent::query()
                ->with(['attendanceDevice', 'normalizedPunch'])
                ->whereKey($rawEvent)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->normalizedPunch !== null && $event->processing_status === AttendanceRawEventStatus::Processed) {
                return $event;
            }

            $localTimestamp = CarbonImmutable::parse($event->punched_at_utc)->setTimezone($event->timezone);
            $mapping = AttendanceDeviceUserMapping::query()
                ->where('company_id', $event->company_id)
                ->where('attendance_device_id', $event->attendance_device_id)
                ->where('external_user_id', $event->external_user_id)
                ->whereDate('effective_from', '<=', $localTimestamp->toDateString())
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $localTimestamp->toDateString()))
                ->first();

            if ($mapping === null) {
                $this->mark($event, AttendanceRawEventStatus::Quarantined, 'No effective same-device Employment mapping exists.');

                return $event->refresh();
            }

            if ($event->direction === null) {
                $this->mark(
                    $event,
                    AttendanceRawEventStatus::RequiresReview,
                    'Punch direction is missing and cannot be inferred safely.',
                    $mapping,
                );

                return $event->refresh();
            }

            $attendanceDate = $this->resolveAttendanceDate($mapping->employment_id, $localTimestamp);
            $record = AttendanceRecord::query()
                ->where('company_id', $event->company_id)
                ->where('employment_id', $mapping->employment_id)
                ->whereDate('attendance_date', $attendanceDate)
                ->first();

            if ($record?->state === AttendanceRecordState::Finalized) {
                $this->mark(
                    $event,
                    AttendanceRawEventStatus::RequiresReview,
                    'The daily Attendance record is finalized; use an approved correction/recalculation workflow.',
                    $mapping,
                );

                return $event->refresh();
            }

            $punch = AttendancePunch::query()->firstOrCreate(
                ['attendance_raw_event_id' => $event->getKey()],
                [
                    'company_id' => $event->company_id,
                    'employment_id' => $mapping->employment_id,
                    'punched_at' => $localTimestamp,
                    'direction' => $event->direction,
                    'source' => AttendancePunchSource::Machine,
                    'status' => AttendancePunchStatus::Approved,
                    'reason' => 'Normalized immutable Attendance device event.',
                    'created_by_id' => null,
                    'approved_by_id' => null,
                    'approved_at' => $event->received_at,
                ],
            );

            $record ??= AttendanceRecord::query()->create([
                'company_id' => $event->company_id,
                'employment_id' => $mapping->employment_id,
                'attendance_date' => $attendanceDate,
                'day_status' => AttendanceDayStatus::MissingPunch,
                'state' => AttendanceRecordState::Draft,
            ]);

            $this->mark($event, AttendanceRawEventStatus::Processed, null, $mapping);

            activity('attendance_raw_events')
                ->performedOn($event)
                ->event('normalized')
                ->withProperties([
                    'company_id' => $event->company_id,
                    'attendance_punch_id' => $punch->getKey(),
                    'attendance_record_id' => $record->getKey(),
                ])
                ->log('normalized raw Attendance event');

            return $event->refresh();
        }, 3);
    }

    private function resolveAttendanceDate(int $employmentId, CarbonImmutable $localTimestamp): string
    {
        $previousDate = $localTimestamp->subDay()->toDateString();
        $overnightAssignment = ShiftAssignment::query()
            ->with('workShift')
            ->where('employment_id', $employmentId)
            ->whereDate('effective_from', '<=', $previousDate)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $previousDate))
            ->get()
            ->first(function (ShiftAssignment $assignment) use ($localTimestamp, $previousDate): bool {
                if (! $assignment->workShift->is_overnight) {
                    return false;
                }

                $startsAt = CarbonImmutable::parse($previousDate.' '.$assignment->workShift->starts_at);
                $endsAt = CarbonImmutable::parse($previousDate.' '.$assignment->workShift->ends_at)->addDay();

                return $localTimestamp->betweenIncluded($startsAt->subHours(6), $endsAt->addHours(6));
            });

        return $overnightAssignment === null
            ? $localTimestamp->toDateString()
            : $previousDate;
    }

    private function mark(
        AttendanceRawEvent $event,
        AttendanceRawEventStatus $status,
        ?string $error,
        ?AttendanceDeviceUserMapping $mapping = null,
    ): void {
        AttendanceRawEvent::query()->whereKey($event)->update([
            'attendance_device_user_mapping_id' => $mapping?->getKey(),
            'employment_id' => $mapping?->employment_id,
            'processing_status' => $status->value,
            'processing_error' => $error,
            'processed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
