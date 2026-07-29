<?php

namespace App\Actions\HR;

use App\Data\AttendanceEventData;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceDevice;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceRawEvent;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IngestAttendanceEventAction
{
    public function __construct(
        private NormalizeAttendanceRawEventAction $normalizeRawEvent,
    ) {}

    /**
     * @return array{event: AttendanceRawEvent, duplicate: bool}
     */
    public function handle(
        AttendanceImportBatch $batch,
        AttendanceEventData $data,
    ): array {
        return DB::transaction(function () use ($batch, $data): array {
            $device = AttendanceDevice::query()
                ->where('company_id', $batch->company_id)
                ->where('code', $data->deviceCode)
                ->where('is_active', true)
                ->first();

            if ($device === null) {
                throw ValidationException::withMessages(['device_code' => 'The active device code does not belong to this company.']);
            }

            if ($batch->attendance_device_id !== null && (int) $batch->attendance_device_id !== (int) $device->getKey()) {
                throw ValidationException::withMessages(['device_code' => 'The event device does not match the sync batch device.']);
            }

            if (! in_array($data->timezone, DateTimeZone::listIdentifiers(), true) || $data->timezone !== $device->timezone) {
                throw ValidationException::withMessages(['timezone' => 'The event timezone must match the configured device timezone.']);
            }

            $localTimestamp = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data->punchedAtLocal, $data->timezone);
            if ($localTimestamp === false || $localTimestamp->format('Y-m-d H:i:s') !== $data->punchedAtLocal) {
                throw ValidationException::withMessages(['punched_at_local' => 'Use the exact YYYY-MM-DD HH:MM:SS format.']);
            }

            $externalUserId = trim($data->externalUserId);
            if ($externalUserId === '') {
                throw ValidationException::withMessages(['external_user_id' => 'The external user ID is required.']);
            }

            $utcTimestamp = $localTimestamp->utc();
            $fingerprint = hash('sha256', implode('|', [
                $device->getKey(),
                $externalUserId,
                $utcTimestamp->format('Y-m-d H:i:s.u'),
                $data->direction?->value ?? '',
            ]));

            $existing = AttendanceRawEvent::query()
                ->where('company_id', $batch->company_id)
                ->where('attendance_device_id', $device->getKey())
                ->where(function ($query) use ($data, $fingerprint): void {
                    $query->where('event_fingerprint', $fingerprint);
                    if ($data->sourceEventId !== null && trim($data->sourceEventId) !== '') {
                        $query->orWhere('source_event_id', trim($data->sourceEventId));
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->processing_status !== AttendanceRawEventStatus::Processed) {
                    $this->normalizeRawEvent->handle($existing);
                }

                return ['event' => $existing->refresh(), 'duplicate' => true];
            }

            $now = now();
            $inserted = AttendanceRawEvent::query()->insertOrIgnore([
                'company_id' => $batch->company_id,
                'attendance_device_id' => $device->getKey(),
                'attendance_import_batch_id' => $batch->getKey(),
                'external_user_id' => $externalUserId,
                'original_punched_at_local' => $data->punchedAtLocal,
                'timezone' => $data->timezone,
                'punched_at_utc' => $utcTimestamp,
                'direction' => $data->direction?->value,
                'source_event_id' => filled($data->sourceEventId) ? trim($data->sourceEventId) : null,
                'safe_payload' => json_encode($data->safePayload, JSON_THROW_ON_ERROR),
                'event_fingerprint' => $fingerprint,
                'processing_status' => AttendanceRawEventStatus::Pending->value,
                'received_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $event = AttendanceRawEvent::query()
                ->where('company_id', $batch->company_id)
                ->where('attendance_device_id', $device->getKey())
                ->where(function ($query) use ($data, $fingerprint): void {
                    $query->where('event_fingerprint', $fingerprint);
                    if (filled($data->sourceEventId)) {
                        $query->orWhere('source_event_id', trim($data->sourceEventId));
                    }
                })
                ->firstOrFail();

            if ($inserted === 0) {
                if ($event->processing_status !== AttendanceRawEventStatus::Processed) {
                    $this->normalizeRawEvent->handle($event);
                }

                return ['event' => $event->refresh(), 'duplicate' => true];
            }

            activity('attendance_raw_events')->performedOn($event)->event('received')
                ->withProperties(['company_id' => $event->company_id, 'attendance_device_id' => $event->attendance_device_id])
                ->log('received immutable Attendance event');

            return [
                'event' => $this->normalizeRawEvent->handle($event),
                'duplicate' => false,
            ];
        }, 3);
    }
}
