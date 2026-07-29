<?php

namespace App\Models;

use App\Enums\MissingPunchTreatment;
use Database\Factories\AttendanceRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'name', 'effective_from', 'effective_to', 'grace_minutes', 'late_rounding_minutes', 'half_day_after_minutes', 'absence_after_minutes', 'minimum_overtime_minutes', 'missing_punch_treatment', 'is_active'])]
class AttendanceRule extends Model
{
    /** @use HasFactory<AttendanceRuleFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (AttendanceRule $rule): void {
            if ($rule->effective_to !== null && $rule->effective_to->lt($rule->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }
            if ($rule->half_day_after_minutes >= $rule->absence_after_minutes) {
                throw ValidationException::withMessages(['absence_after_minutes' => 'Absence threshold must be greater than the half-day threshold.']);
            }
            $overlap = self::query()->where('company_id', $rule->company_id)->where('is_active', true)->whereKeyNot($rule)
                ->whereDate('effective_from', '<=', $rule->effective_to ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $rule->effective_from))->exists();
            if ($rule->is_active && $overlap) {
                throw ValidationException::withMessages(['effective_from' => 'Active attendance rules cannot overlap.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attendance_rules')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'missing_punch_treatment' => MissingPunchTreatment::class, 'is_active' => 'boolean'];
    }
}
