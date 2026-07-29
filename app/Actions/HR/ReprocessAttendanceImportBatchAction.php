<?php

namespace App\Actions\HR;

use App\Enums\AttendanceImportSource;
use App\Models\AttendanceImportBatch;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReprocessAttendanceImportBatchAction
{
    public function __construct(
        private ImportAttendanceCsvAction $importCsv,
    ) {}

    public function handle(AttendanceImportBatch $batch, User $actor): AttendanceImportBatch
    {
        Gate::forUser($actor)->authorize('reprocess', $batch);

        if ($batch->source !== AttendanceImportSource::Csv || $batch->stored_file_path === null) {
            throw ValidationException::withMessages([
                'source' => 'Only retained CSV batches can be reprocessed without a device adapter.',
            ]);
        }

        return $this->importCsv->handle(
            company: $batch->company,
            storedFilePath: $batch->stored_file_path,
            originalFilename: $batch->original_filename ?? 'attendance.csv',
            actor: $actor,
            reprocessOf: $batch,
        );
    }
}
