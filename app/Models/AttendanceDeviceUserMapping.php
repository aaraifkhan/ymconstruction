<?php

namespace App\Models;

use Database\Factories\AttendanceDeviceUserMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'attendance_device_id', 'employment_id', 'external_user_id',
    'effective_from', 'effective_to', 'notes',
])]
class AttendanceDeviceUserMapping extends Model
{
    /** @use HasFactory<AttendanceDeviceUserMappingFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (AttendanceDeviceUserMapping $mapping): void {
            foreach ([
                'attendance_device_id' => AttendanceDevice::class,
                'employment_id' => Employment::class,
            ] as $field => $model) {
                if (! $model::query()->whereKey($mapping->{$field})->where('company_id', $mapping->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'The selected record must belong to the same company.']);
                }
            }

            $mapping->external_user_id = trim($mapping->external_user_id);
            if ($mapping->external_user_id === '') {
                throw ValidationException::withMessages(['external_user_id' => 'The external user ID is required.']);
            }

            if ($mapping->effective_to !== null && $mapping->effective_to->lt($mapping->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }

            $overlaps = self::query()
                ->where('attendance_device_id', $mapping->attendance_device_id)
                ->where('external_user_id', $mapping->external_user_id)
                ->whereKeyNot($mapping)
                ->whereDate('effective_from', '<=', $mapping->effective_to ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $mapping->effective_from))
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['effective_from' => 'Device user mappings cannot overlap.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceDevice(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attendance_device_user_mappings')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date'];
    }
}
