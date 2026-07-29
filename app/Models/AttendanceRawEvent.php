<?php

namespace App\Models;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use Database\Factories\AttendanceRawEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'attendance_device_id', 'attendance_import_batch_id',
    'attendance_device_user_mapping_id', 'employment_id', 'external_user_id',
    'original_punched_at_local', 'timezone', 'punched_at_utc', 'direction',
    'source_event_id', 'safe_payload', 'event_fingerprint', 'processing_status',
    'processing_error', 'received_at', 'processed_at',
])]
class AttendanceRawEvent extends Model
{
    /** @use HasFactory<AttendanceRawEventFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['processing_status' => 'pending'];

    protected static function booted(): void
    {
        static::creating(function (AttendanceRawEvent $event): void {
            if (! AttendanceDevice::query()->whereKey($event->attendance_device_id)->where('company_id', $event->company_id)->exists()) {
                throw ValidationException::withMessages(['attendance_device_id' => 'The device must belong to the same company.']);
            }

            foreach ([
                'attendance_import_batch_id' => AttendanceImportBatch::class,
                'attendance_device_user_mapping_id' => AttendanceDeviceUserMapping::class,
                'employment_id' => Employment::class,
            ] as $field => $model) {
                if ($event->{$field} !== null && ! $model::query()->whereKey($event->{$field})->where('company_id', $event->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'The selected record must belong to the same company.']);
                }
            }
        });

        static::updating(fn () => throw ValidationException::withMessages([
            'processing_status' => 'Raw Attendance events are immutable and processed only by controlled workflows.',
        ]));
        static::deleting(fn () => throw ValidationException::withMessages([
            'processing_status' => 'Raw Attendance events cannot be deleted.',
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

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'attendance_import_batch_id');
    }

    public function deviceUserMapping(): BelongsTo
    {
        return $this->belongsTo(AttendanceDeviceUserMapping::class, 'attendance_device_user_mapping_id');
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function normalizedPunch(): HasOne
    {
        return $this->hasOne(AttendancePunch::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attendance_raw_events')
            ->logOnly([
                'company_id', 'attendance_device_id', 'attendance_import_batch_id',
                'attendance_device_user_mapping_id', 'employment_id', 'external_user_id',
                'original_punched_at_local', 'timezone', 'punched_at_utc', 'direction',
                'source_event_id', 'event_fingerprint', 'processing_status',
                'processing_error', 'received_at', 'processed_at',
            ])->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'punched_at_utc' => 'datetime',
            'direction' => AttendancePunchDirection::class,
            'safe_payload' => 'array',
            'processing_status' => AttendanceRawEventStatus::class,
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
