<?php

namespace App\Models;

use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceDeviceTransport;
use Database\Factories\AttendanceDeviceFactory;
use DateTimeZone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'work_location_id', 'code', 'name', 'device_identifier',
    'timezone', 'transport', 'connection_profile_reference', 'health_status',
    'is_active', 'last_sync_at', 'last_seen_at', 'last_cursor', 'last_error_summary',
])]
class AttendanceDevice extends Model
{
    /** @use HasFactory<AttendanceDeviceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'transport' => 'unknown',
        'health_status' => 'unknown',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (AttendanceDevice $device): void {
            if (! in_array($device->timezone, DateTimeZone::listIdentifiers(), true)) {
                throw ValidationException::withMessages(['timezone' => 'Select a valid IANA timezone.']);
            }

            if ($device->work_location_id !== null && ! WorkLocation::query()
                ->whereKey($device->work_location_id)
                ->where('company_id', $device->company_id)
                ->exists()) {
                throw ValidationException::withMessages(['work_location_id' => 'The Work Location must belong to the same company.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function userMappings(): HasMany
    {
        return $this->hasMany(AttendanceDeviceUserMapping::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(AttendanceImportBatch::class);
    }

    public function rawEvents(): HasMany
    {
        return $this->hasMany(AttendanceRawEvent::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attendance_devices')
            ->logOnly([
                'company_id', 'work_location_id', 'code', 'name', 'device_identifier',
                'timezone', 'transport', 'connection_profile_reference', 'health_status',
                'is_active', 'last_sync_at', 'last_seen_at', 'last_error_summary',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'transport' => AttendanceDeviceTransport::class,
            'health_status' => AttendanceDeviceHealthStatus::class,
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
