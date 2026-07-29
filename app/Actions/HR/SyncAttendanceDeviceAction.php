<?php

namespace App\Actions\HR;

use App\Contracts\AttendanceDeviceAdapter;
use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceDevice;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportRowError;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SyncAttendanceDeviceAction
{
    public function __construct(
        private IngestAttendanceEventAction $ingestEvent,
    ) {}

    public function handle(
        AttendanceDevice $device,
        AttendanceDeviceAdapter $adapter,
        User $actor,
    ): AttendanceImportBatch {
        Gate::forUser($actor)->authorize('sync', $device);

        try {
            $result = $adapter->pull($device, $device->last_cursor);
        } catch (Throwable $exception) {
            $device->update([
                'health_status' => AttendanceDeviceHealthStatus::Error,
                'last_sync_at' => now(),
                'last_error_summary' => 'The device adapter could not complete synchronization.',
            ]);
            activity('attendance_devices')->causedBy($actor)->performedOn($device)->event('sync_failed')
                ->withProperties(['company_id' => $device->company_id])
                ->log('failed Attendance device synchronization');

            throw $exception;
        }

        $batchChecksum = hash('sha256', json_encode([
            'device_id' => $device->getKey(),
            'cursor_before' => $device->last_cursor,
            'cursor_after' => $result->nextCursor,
            'events' => array_map(
                fn ($event): array => [
                    $event->deviceCode,
                    $event->externalUserId,
                    $event->punchedAtLocal,
                    $event->timezone,
                    $event->direction?->value,
                    $event->sourceEventId,
                ],
                $result->events,
            ),
        ], JSON_THROW_ON_ERROR));

        $existing = AttendanceImportBatch::query()
            ->where('company_id', $device->company_id)
            ->where('source', AttendanceImportSource::DeviceAdapter)
            ->where('batch_checksum', $batchChecksum)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        $batch = AttendanceImportBatch::query()->create([
            'company_id' => $device->company_id,
            'attendance_device_id' => $device->getKey(),
            'source' => AttendanceImportSource::DeviceAdapter,
            'status' => AttendanceImportBatchStatus::Processing,
            'batch_checksum' => $batchChecksum,
            'cursor_before' => $device->last_cursor,
            'cursor_after' => $result->nextCursor,
            'source_metadata' => $result->safeMetadata,
            'initiated_by_id' => $actor->getKey(),
            'started_at' => now(),
        ]);

        $counts = ['accepted' => 0, 'duplicates' => 0, 'quarantined' => 0, 'errors' => 0];
        foreach ($result->events as $index => $eventData) {
            try {
                $resultRow = $this->ingestEvent->handle($batch, $eventData);
                $counts[$resultRow['duplicate'] ? 'duplicates' : 'accepted']++;
                if ($resultRow['event']->processing_status === AttendanceRawEventStatus::Quarantined) {
                    $counts['quarantined']++;
                }
            } catch (Throwable $exception) {
                $counts['errors']++;
                AttendanceImportRowError::query()->create([
                    'company_id' => $batch->company_id,
                    'attendance_import_batch_id' => $batch->getKey(),
                    'row_number' => $index + 1,
                    'error_code' => 'adapter_event_failed',
                    'external_reference' => $eventData->sourceEventId,
                    'message' => 'The adapter event could not be ingested.',
                    'safe_row_data' => [
                        'device_code' => $eventData->deviceCode,
                        'external_user_id' => $eventData->externalUserId,
                        'punched_at_local' => $eventData->punchedAtLocal,
                    ],
                ]);
                report($exception);
            }
        }

        $status = ($counts['errors'] > 0 || $counts['quarantined'] > 0)
            ? AttendanceImportBatchStatus::CompletedWithErrors
            : AttendanceImportBatchStatus::Completed;
        AttendanceImportBatch::query()->whereKey($batch)->update([
            'status' => $status->value,
            'row_count' => count($result->events),
            'accepted_count' => $counts['accepted'],
            'duplicate_count' => $counts['duplicates'],
            'quarantined_count' => $counts['quarantined'],
            'error_count' => $counts['errors'],
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        $device->update([
            'health_status' => AttendanceDeviceHealthStatus::Online,
            'last_sync_at' => now(),
            'last_seen_at' => count($result->events) > 0 ? now() : $device->last_seen_at,
            'last_cursor' => $result->nextCursor,
            'last_error_summary' => $counts['errors'] > 0 ? "{$counts['errors']} adapter event(s) failed." : null,
        ]);

        activity('attendance_import_batches')->causedBy($actor)->performedOn($batch)->event('synced')
            ->withProperties(['company_id' => $device->company_id, 'attendance_device_id' => $device->getKey()])
            ->log('synchronized Attendance device adapter batch');

        return $batch->refresh();
    }
}
