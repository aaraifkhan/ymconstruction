<?php

namespace App\Models;

use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use Database\Factories\AttendanceImportBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'attendance_device_id', 'source', 'status', 'original_filename',
    'stored_file_path', 'batch_checksum', 'cursor_before', 'cursor_after',
    'source_metadata', 'row_count', 'accepted_count', 'duplicate_count',
    'quarantined_count', 'error_count', 'initiated_by_id', 'started_at',
    'completed_at', 'failure_summary',
])]
class AttendanceImportBatch extends Model
{
    /** @use HasFactory<AttendanceImportBatchFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => 'pending',
        'row_count' => 0,
        'accepted_count' => 0,
        'duplicate_count' => 0,
        'quarantined_count' => 0,
        'error_count' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (AttendanceImportBatch $batch): void {
            if ($batch->attendance_device_id !== null && ! AttendanceDevice::query()
                ->whereKey($batch->attendance_device_id)
                ->where('company_id', $batch->company_id)
                ->exists()) {
                throw ValidationException::withMessages(['attendance_device_id' => 'The device must belong to the same company.']);
            }
        });

        static::updating(fn () => throw ValidationException::withMessages([
            'status' => 'Attendance import batches are updated only by controlled ingestion workflows.',
        ]));
        static::deleting(fn () => throw ValidationException::withMessages([
            'status' => 'Attendance import batches cannot be deleted.',
        ]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceDevice(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    public function rawEvents(): HasMany
    {
        return $this->hasMany(AttendanceRawEvent::class);
    }

    public function rowErrors(): HasMany
    {
        return $this->hasMany(AttendanceImportRowError::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attendance_import_batches')
            ->logOnly([
                'company_id', 'attendance_device_id', 'source', 'status',
                'original_filename', 'batch_checksum', 'cursor_before', 'cursor_after',
                'row_count', 'accepted_count', 'duplicate_count', 'quarantined_count',
                'error_count', 'initiated_by_id', 'started_at', 'completed_at',
                'failure_summary',
            ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'source' => AttendanceImportSource::class,
            'status' => AttendanceImportBatchStatus::class,
            'source_metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
