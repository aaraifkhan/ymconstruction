<?php

namespace App\Models;

use Database\Factories\WorkCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'code', 'name', 'timezone', 'working_weekdays', 'effective_from', 'effective_to', 'is_active'])]
class WorkCalendar extends Model
{
    /** @use HasFactory<WorkCalendarFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['timezone' => 'Asia/Karachi', 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (WorkCalendar $calendar): void {
            $weekdays = collect($calendar->working_weekdays)->map(fn ($day): int => (int) $day)->unique()->sort()->values();

            if ($weekdays->isEmpty() || $weekdays->contains(fn (int $day): bool => $day < 1 || $day > 7)) {
                throw ValidationException::withMessages(['working_weekdays' => 'Select valid ISO weekdays from 1 to 7.']);
            }

            if ($calendar->effective_to !== null && $calendar->effective_to->lt($calendar->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }

            $calendar->working_weekdays = $weekdays->all();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(CompanyHoliday::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function isWorkingDay(\DateTimeInterface $date): bool
    {
        return in_array((int) $date->format('N'), $this->working_weekdays, true)
            && ! $this->holidays()->whereDate('holiday_date', $date)->where('is_active', true)->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('work_calendars')->logOnly([
            'company_id', 'code', 'name', 'timezone', 'working_weekdays', 'effective_from', 'effective_to', 'is_active',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['working_weekdays' => 'array', 'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }
}
