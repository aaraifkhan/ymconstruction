<?php

namespace App\Models;

use App\Enums\AttendanceSummaryStatus;
use Database\Factories\AttendanceMonthlySummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'period_start', 'period_end', 'status',
    'scheduled_days', 'scheduled_minutes', 'present_days', 'absent_days', 'half_days', 'leave_days',
    'late_minutes', 'overtime_minutes', 'unpaid_leave_units', 'unpaid_leave_days', 'source_checksum',
    'finalized_by_id', 'finalized_at',
])]
class AttendanceMonthlySummary extends Model
{
    /** @use HasFactory<AttendanceMonthlySummaryFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (AttendanceMonthlySummary $summary): void {
            if ($summary->exists && $summary->getRawOriginal('status') === AttendanceSummaryStatus::Finalized->value) {
                throw ValidationException::withMessages(['status' => 'Finalized attendance summaries are immutable Payroll inputs.']);
            }

            if ($summary->period_end->lt($summary->period_start)) {
                throw ValidationException::withMessages(['period_end' => 'The period end must be on or after the start.']);
            }
        });

        static::deleting(function (AttendanceMonthlySummary $summary): void {
            if ($summary->status === AttendanceSummaryStatus::Finalized) {
                throw ValidationException::withMessages(['status' => 'Finalized attendance summaries cannot be deleted.']);
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

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attendance_monthly_summaries')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => AttendanceSummaryStatus::class,
            'unpaid_leave_units' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:4',
            'finalized_at' => 'datetime',
        ];
    }
}
