<?php

namespace App\Models;

use Database\Factories\WorkShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'code', 'name', 'starts_at', 'ends_at', 'break_minutes', 'is_overnight', 'is_active'])]
class WorkShift extends Model
{
    /** @use HasFactory<WorkShiftFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['break_minutes' => 0, 'is_overnight' => false, 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (WorkShift $shift): void {
            if ($shift->starts_at === $shift->ends_at) {
                throw ValidationException::withMessages(['ends_at' => 'Shift start and end times cannot be equal.']);
            }
            if (! $shift->is_overnight && $shift->ends_at < $shift->starts_at) {
                throw ValidationException::withMessages(['is_overnight' => 'Mark the shift as overnight when it ends on the next day.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('work_shifts')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['break_minutes' => 'integer', 'is_overnight' => 'boolean', 'is_active' => 'boolean'];
    }
}
