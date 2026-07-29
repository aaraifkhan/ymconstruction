<?php

namespace App\Models;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceRecordState;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'shift_assignment_id', 'attendance_rule_id',
    'attendance_date', 'day_status', 'state', 'first_in_at', 'last_out_at',
    'scheduled_minutes', 'worked_minutes', 'late_minutes', 'overtime_minutes',
    'source_checksum', 'notes', 'finalized_by_id', 'finalized_at',
])]
class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['state' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (AttendanceRecord $record): void {
            if ($record->exists && $record->getRawOriginal('state') === AttendanceRecordState::Finalized->value) {
                throw ValidationException::withMessages(['state' => 'Finalized attendance records are immutable.']);
            }

            if (! Employment::query()->whereKey($record->employment_id)->where('company_id', $record->company_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'The employment must belong to the same company.']);
            }
        });

        static::deleting(function (AttendanceRecord $record): void {
            if ($record->state === AttendanceRecordState::Finalized) {
                throw ValidationException::withMessages(['state' => 'Finalized attendance records cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function attendanceRule(): BelongsTo
    {
        return $this->belongsTo(AttendanceRule::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attendance_records')
            ->logOnlyDirty()
            ->logFillable()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'day_status' => AttendanceDayStatus::class,
            'state' => AttendanceRecordState::class,
            'first_in_at' => 'datetime',
            'last_out_at' => 'datetime',
            'scheduled_minutes' => 'integer',
            'worked_minutes' => 'integer',
            'late_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'finalized_at' => 'datetime',
        ];
    }
}
