<?php

namespace App\Models;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchSource;
use App\Enums\AttendancePunchStatus;
use Database\Factories\AttendancePunchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'punched_at', 'direction', 'source',
    'attendance_raw_event_id', 'status', 'reason',
    'created_by_id', 'approved_by_id', 'approved_at', 'rejection_reason',
])]
class AttendancePunch extends Model
{
    /** @use HasFactory<AttendancePunchFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['source' => 'manual', 'status' => 'pending'];

    protected static function booted(): void
    {
        static::saving(function (AttendancePunch $punch): void {
            if ($punch->exists && $punch->getRawOriginal('status') !== AttendancePunchStatus::Pending->value) {
                throw ValidationException::withMessages(['status' => 'Decided attendance punches are immutable.']);
            }

            if (! Employment::query()->whereKey($punch->employment_id)->where('company_id', $punch->company_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'The employment must belong to the same company.']);
            }

            if ($punch->source === AttendancePunchSource::Machine) {
                if ($punch->attendance_raw_event_id === null || $punch->created_by_id !== null) {
                    throw ValidationException::withMessages(['attendance_raw_event_id' => 'Machine punches require one raw event and no manual creator.']);
                }
            } elseif ($punch->attendance_raw_event_id !== null || $punch->created_by_id === null) {
                throw ValidationException::withMessages(['created_by_id' => 'Manual punches require a creator and cannot reference a raw event.']);
            }
        });

        static::deleting(fn (AttendancePunch $punch) => throw ValidationException::withMessages([
            'status' => 'Attendance punch evidence cannot be deleted.',
        ]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function attendanceRawEvent(): BelongsTo
    {
        return $this->belongsTo(AttendanceRawEvent::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attendance_punches')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'direction' => AttendancePunchDirection::class,
            'source' => AttendancePunchSource::class,
            'status' => AttendancePunchStatus::class,
            'approved_at' => 'datetime',
        ];
    }
}
