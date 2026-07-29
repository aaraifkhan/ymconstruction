<?php

namespace App\Models;

use Database\Factories\ShiftAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'employment_id', 'work_calendar_id', 'work_shift_id', 'effective_from', 'effective_to', 'notes'])]
class ShiftAssignment extends Model
{
    /** @use HasFactory<ShiftAssignmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (ShiftAssignment $assignment): void {
            foreach (['employment_id' => Employment::class, 'work_calendar_id' => WorkCalendar::class, 'work_shift_id' => WorkShift::class] as $field => $model) {
                if (! $model::query()->whereKey($assignment->{$field})->where('company_id', $assignment->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'The selected record must belong to the same company.']);
                }
            }
            if ($assignment->effective_to !== null && $assignment->effective_to->lt($assignment->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }
            $overlap = self::query()->where('employment_id', $assignment->employment_id)->whereKeyNot($assignment)
                ->whereDate('effective_from', '<=', $assignment->effective_to ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $assignment->effective_from))
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['effective_from' => 'Shift assignments cannot overlap for an employment.']);
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

    public function workCalendar(): BelongsTo
    {
        return $this->belongsTo(WorkCalendar::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('shift_assignments')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date'];
    }
}
