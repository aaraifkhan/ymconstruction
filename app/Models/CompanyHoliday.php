<?php

namespace App\Models;

use Database\Factories\CompanyHolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'work_calendar_id', 'name', 'holiday_date', 'is_paid', 'is_active'])]
class CompanyHoliday extends Model
{
    /** @use HasFactory<CompanyHolidayFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_paid' => true, 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (CompanyHoliday $holiday): void {
            if (! WorkCalendar::query()->whereKey($holiday->work_calendar_id)->where('company_id', $holiday->company_id)->exists()) {
                throw ValidationException::withMessages(['work_calendar_id' => 'The calendar must belong to the same company.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workCalendar(): BelongsTo
    {
        return $this->belongsTo(WorkCalendar::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('company_holidays')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['holiday_date' => 'date', 'is_paid' => 'boolean', 'is_active' => 'boolean'];
    }
}
