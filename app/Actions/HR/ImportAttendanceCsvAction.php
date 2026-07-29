<?php

namespace App\Actions\HR;

use App\Data\AttendanceEventData;
use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportRowError;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportAttendanceCsvAction
{
    /** @var list<string> */
    private const HEADERS = [
        'device_code',
        'external_user_id',
        'punched_at_local',
        'timezone',
        'direction',
        'source_event_id',
    ];

    public function __construct(
        private IngestAttendanceEventAction $ingestEvent,
    ) {}

    public function handle(
        Company $company,
        string $storedFilePath,
        string $originalFilename,
        User $actor,
        ?AttendanceImportBatch $reprocessOf = null,
    ): AttendanceImportBatch {
        Gate::forUser($actor)->authorize('import', [AttendanceImportBatch::class, $company]);

        if (! Storage::disk('local')->exists($storedFilePath)) {
            throw ValidationException::withMessages(['file' => 'The private CSV file is unavailable.']);
        }

        $fileSize = Storage::disk('local')->size($storedFilePath);
        if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'The CSV must be between 1 byte and 10 MB.']);
        }

        $fileChecksum = hash_file('sha256', Storage::disk('local')->path($storedFilePath));
        $batchChecksum = $reprocessOf === null
            ? $fileChecksum
            : hash('sha256', "{$fileChecksum}|reprocess|{$reprocessOf->getKey()}|".now()->format('Y-m-d H:i:s.u'));

        if ($reprocessOf === null) {
            $existing = AttendanceImportBatch::query()
                ->where('company_id', $company->getKey())
                ->where('source', AttendanceImportSource::Csv)
                ->where('batch_checksum', $batchChecksum)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $batch = AttendanceImportBatch::query()->create([
            'company_id' => $company->getKey(),
            'source' => AttendanceImportSource::Csv,
            'status' => AttendanceImportBatchStatus::Processing,
            'original_filename' => $originalFilename,
            'stored_file_path' => $storedFilePath,
            'batch_checksum' => $batchChecksum,
            'source_metadata' => array_filter([
                'file_checksum' => $fileChecksum,
                'reprocess_of_batch_id' => $reprocessOf?->getKey(),
            ]),
            'initiated_by_id' => $actor->getKey(),
            'started_at' => now(),
        ]);

        $handle = fopen(Storage::disk('local')->path($storedFilePath), 'rb');
        if ($handle === false) {
            $this->finishFailed($batch, 'The private CSV could not be opened.');

            return $batch->refresh();
        }

        try {
            $headers = fgetcsv($handle);
            $headers = is_array($headers)
                ? array_map(fn (string $header): string => trim($header, "\xEF\xBB\xBF \t\n\r\0\x0B"), $headers)
                : [];

            if ($headers !== self::HEADERS) {
                $this->recordError($batch, 1, 'invalid_header', null, 'The CSV header must exactly match the approved six-column contract.', [
                    'received_headers' => implode(',', $headers),
                ]);
                $this->finishFailed($batch, 'Invalid CSV header.', errorCount: 1);

                return $batch->refresh();
            }

            $counts = ['rows' => 0, 'accepted' => 0, 'duplicates' => 0, 'quarantined' => 0, 'errors' => 0];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                    continue;
                }

                $counts['rows']++;
                if (count($row) !== count(self::HEADERS)) {
                    $counts['errors']++;
                    $this->recordError($batch, $rowNumber, 'invalid_column_count', null, 'Each row must contain exactly six columns.', [
                        'column_count' => count($row),
                    ]);

                    continue;
                }

                $values = array_combine(self::HEADERS, array_map(fn ($value): string => trim((string) $value), $row));

                try {
                    $direction = $values['direction'] === ''
                        ? null
                        : AttendancePunchDirection::tryFrom(strtolower($values['direction']));
                    if ($values['direction'] !== '' && $direction === null) {
                        throw ValidationException::withMessages(['direction' => 'Direction must be in, out, break_out, break_in, or blank.']);
                    }

                    $result = $this->ingestEvent->handle($batch, new AttendanceEventData(
                        deviceCode: $values['device_code'],
                        externalUserId: $values['external_user_id'],
                        punchedAtLocal: $values['punched_at_local'],
                        timezone: $values['timezone'],
                        direction: $direction,
                        sourceEventId: $values['source_event_id'] === '' ? null : $values['source_event_id'],
                        source: AttendanceImportSource::Csv,
                        safePayload: ['row_number' => $rowNumber],
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
                        : 'The row could not be ingested.';
                    $this->recordError(
                        $batch,
                        $rowNumber,
                        $exception instanceof ValidationException ? 'validation_failed' : 'ingestion_failed',
                        $values['source_event_id'] ?: null,
                        $message,
                        [
                            'device_code' => $values['device_code'],
                            'external_user_id' => $values['external_user_id'],
                            'punched_at_local' => $values['punched_at_local'],
                        ],
                    );
                }
            }

            $status = ($counts['errors'] > 0 || $counts['quarantined'] > 0)
                ? AttendanceImportBatchStatus::CompletedWithErrors
                : AttendanceImportBatchStatus::Completed;
            AttendanceImportBatch::query()->whereKey($batch)->update([
                'status' => $status->value,
                'row_count' => $counts['rows'],
                'accepted_count' => $counts['accepted'],
                'duplicate_count' => $counts['duplicates'],
                'quarantined_count' => $counts['quarantined'],
                'error_count' => $counts['errors'],
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->finishFailed($batch, 'The CSV import stopped unexpectedly.');
        } finally {
            fclose($handle);
        }

        activity('attendance_import_batches')->causedBy($actor)->performedOn($batch)->event('imported')
            ->withProperties(['company_id' => $company->getKey(), 'batch_checksum' => $batchChecksum])
            ->log('processed Attendance CSV import');

        return $batch->refresh();
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

    private function finishFailed(AttendanceImportBatch $batch, string $summary, int $errorCount = 0): void
    {
        AttendanceImportBatch::query()->whereKey($batch)->update([
            'status' => AttendanceImportBatchStatus::Failed->value,
            'error_count' => $errorCount,
            'failure_summary' => $summary,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
