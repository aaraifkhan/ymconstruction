<?php

namespace App\Actions\HR;

use App\Data\AttendanceEventData;
use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceDevice;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportRowError;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessZkTecoAdmsPushAction
{
    public function __construct(
        private IngestAttendanceEventAction $ingestEvent,
    ) {}

    /**
     * Parse and process raw ADMS ATTLOG POST payload body.
     * Returns total lines processed (including duplicates and errors).
     */
    public function handle(AttendanceDevice $device, string $rawPayload): int
    {
        $payloadTrimmed = trim($rawPayload);
        if ($payloadTrimmed === '') {
            AttendanceDevice::query()->whereKey($device)->update([
                'last_seen_at' => now(),
                'health_status' => AttendanceDeviceHealthStatus::Online->value,
            ]);

            return 0;
        }

        $payloadChecksum = hash('sha256', $rawPayload);
        $batchChecksum = hash('sha256', "zkteco-adms|{$device->id}|{$payloadChecksum}|".now()->format('Y-m-d H:i:s.u'));

        $batch = AttendanceImportBatch::query()->create([
            'company_id' => $device->company_id,
            'attendance_device_id' => $device->id,
            'source' => AttendanceImportSource::DeviceAdapter,
            'status' => AttendanceImportBatchStatus::Processing,
            'original_filename' => "adms_push_{$device->code}_".now()->format('YmdHis').'.txt',
            'batch_checksum' => $batchChecksum,
            'source_metadata' => [
                'transport' => 'zkteco_adms',
                'payload_checksum' => $payloadChecksum,
                'payload_bytes' => strlen($rawPayload),
            ],
            'started_at' => now(),
        ]);

        /** @var array<int, string> $lines */
        $lines = preg_split('/\r\n|\r|\n/', $rawPayload) ?: [];
        $counts = ['rows' => 0, 'accepted' => 0, 'duplicates' => 0, 'quarantined' => 0, 'errors' => 0];
        $rowNumber = 0;

        foreach ($lines as $line) {
            $lineTrimmed = trim($line);
            if ($lineTrimmed === '') {
                continue;
            }

            $rowNumber++;
            $counts['rows']++;

            /** @var array<int, string> $parts */
            $parts = preg_split('/\t+|\s+/', $lineTrimmed) ?: [];

            $externalUserId = null;
            $punchedAtLocal = null;
            $statusRaw = null;
            $verifyTypeRaw = null;

            if (count($parts) >= 2) {
                $externalUserId = trim($parts[0]);

                if (isset($parts[1], $parts[2]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parts[1]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $parts[2])) {
                    $punchedAtLocal = $parts[1].' '.$parts[2];
                    $statusRaw = $parts[3] ?? null;
                    $verifyTypeRaw = $parts[4] ?? null;
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $parts[1])) {
                    $punchedAtLocal = $parts[1];
                    $statusRaw = $parts[2] ?? null;
                    $verifyTypeRaw = $parts[3] ?? null;
                }
            }

            if (empty($externalUserId) || empty($punchedAtLocal)) {
                $counts['errors']++;
                $this->recordError(
                    $batch,
                    $rowNumber,
                    'invalid_format',
                    null,
                    'Line could not be parsed into external user ID and timestamp.',
                    ['raw_line' => $lineTrimmed]
                );

                continue;
            }

            $direction = match ($statusRaw) {
                '0', '4' => AttendancePunchDirection::In,
                '1', '5' => AttendancePunchDirection::Out,
                '2' => AttendancePunchDirection::BreakOut,
                '3' => AttendancePunchDirection::BreakIn,
                default => null,
            };

            $sourceEventId = "ADMS-{$device->code}-{$externalUserId}-".str_replace([' ', ':', '-'], '', $punchedAtLocal);

            try {
                $result = $this->ingestEvent->handle($batch, new AttendanceEventData(
                    deviceCode: $device->code,
                    externalUserId: $externalUserId,
                    punchedAtLocal: $punchedAtLocal,
                    timezone: $device->timezone ?: 'Asia/Karachi',
                    direction: $direction,
                    sourceEventId: $sourceEventId,
                    source: AttendanceImportSource::DeviceAdapter,
                    safePayload: [
                        'raw_line' => $lineTrimmed,
                        'status_raw' => $statusRaw,
                        'verify_type_raw' => $verifyTypeRaw,
                    ],
                ));

                if ($result['duplicate']) {
                    $counts['duplicates']++;
                } else {
                    $counts['accepted']++;
                }

                if ($result['event']->processing_status === AttendanceRawEventStatus::Quarantined) {
                    $counts['quarantined']++;
                }
            } catch (Throwable $exception) {
                $counts['errors']++;
                $message = $exception instanceof ValidationException
                    ? (string) collect($exception->errors())->flatten()->first()
                    : 'The ADMS line could not be ingested.';

                $this->recordError(
                    $batch,
                    $rowNumber,
                    'ingestion_failed',
                    $sourceEventId,
                    $message,
                    ['raw_line' => $lineTrimmed]
                );
            }
        }

        $batchStatus = ($counts['errors'] > 0 || $counts['quarantined'] > 0)
            ? AttendanceImportBatchStatus::CompletedWithErrors
            : AttendanceImportBatchStatus::Completed;

        AttendanceImportBatch::query()->whereKey($batch)->update([
            'status' => $batchStatus->value,
            'row_count' => $counts['rows'],
            'accepted_count' => $counts['accepted'],
            'duplicate_count' => $counts['duplicates'],
            'quarantined_count' => $counts['quarantined'],
            'error_count' => $counts['errors'],
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceDevice::query()->whereKey($device)->update([
            'last_sync_at' => now(),
            'last_seen_at' => now(),
            'health_status' => AttendanceDeviceHealthStatus::Online->value,
            'last_error_summary' => null,
        ]);

        return $counts['rows'];
    }

    /**
     * @param  array<string, scalar|null>  $safeRowData
     */
    private function recordError(
        AttendanceImportBatch $batch,
        int $rowNumber,
        string $errorCode,
        ?string $externalReference,
        string $message,
        array $safeRowData,
    ): void {
        AttendanceImportRowError::query()->create([
            'company_id' => $batch->company_id,
            'attendance_import_batch_id' => $batch->getKey(),
            'row_number' => $rowNumber,
            'error_code' => $errorCode,
            'external_reference' => $externalReference,
            'message' => $message,
            'safe_row_data' => $safeRowData,
        ]);
    }
}
