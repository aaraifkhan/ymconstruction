<?php

namespace App\Models;

use App\Enums\AppraisalCycleStatus;
use Database\Factories\AppraisalCycleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'name', 'starts_on', 'ends_on', 'score_min', 'score_max', 'status',
    'activated_by_id', 'activated_at', 'closed_by_id', 'closed_at',
])]
class AppraisalCycle extends Model
{
    /** @use HasFactory<AppraisalCycleFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $cycle): void {
            if ($cycle->ends_on->lt($cycle->starts_on)
                || bccomp((string) $cycle->score_max, (string) $cycle->score_min, 4) !== 1) {
                throw ValidationException::withMessages([
                    'ends_on' => 'Use a valid cycle period and a maximum score greater than the minimum score.',
                ]);
            }
        });
        static::updating(function (self $cycle): void {
            $original = AppraisalCycleStatus::from($cycle->getRawOriginal('status'));
            if ($original !== AppraisalCycleStatus::Draft
                && $cycle->isDirty(array_diff($cycle->getFillable(), [
                    'status', 'activated_by_id', 'activated_at', 'closed_by_id', 'closed_at',
                ]))) {
                throw ValidationException::withMessages(['status' => 'An active or closed appraisal cycle is immutable.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(PerformanceAppraisal::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('appraisal_cycles')
            ->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'score_min' => 'decimal:4',
            'score_max' => 'decimal:4',
            'status' => AppraisalCycleStatus::class,
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
