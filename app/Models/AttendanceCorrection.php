<?php

namespace App\Models;

use App\Enums\AttendanceCorrectionStatus;
use Database\Factories\AttendanceCorrectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'attendance_record_id', 'status', 'before_snapshot',
    'proposed_snapshot', 'reason', 'requested_by_id', 'decided_by_id',
    'decided_at', 'decision_reason',
])]
class AttendanceCorrection extends Model
{
    /** @use HasFactory<AttendanceCorrectionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['status' => 'pending'];

    protected static function booted(): void
    {
        static::saving(function (AttendanceCorrection $correction): void {
            if ($correction->exists && $correction->getRawOriginal('status') !== AttendanceCorrectionStatus::Pending->value) {
                throw ValidationException::withMessages(['status' => 'Decided attendance corrections are immutable.']);
            }

            if (! AttendanceRecord::query()->whereKey($correction->attendance_record_id)->where('company_id', $correction->company_id)->exists()) {
                throw ValidationException::withMessages(['attendance_record_id' => 'The attendance record must belong to the same company.']);
            }
        });

        static::deleting(fn () => throw ValidationException::withMessages([
            'status' => 'Attendance correction evidence cannot be deleted.',
        ]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attendance_corrections')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => AttendanceCorrectionStatus::class,
            'before_snapshot' => 'array',
            'proposed_snapshot' => 'array',
            'decided_at' => 'datetime',
        ];
    }
}
