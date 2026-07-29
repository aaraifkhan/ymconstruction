<?php

namespace App\Models;

use Database\Factories\PayrollCalculationRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'name', 'effective_from', 'effective_to', 'requires_finalized_attendance',
    'prorate_allowances', 'absence_day_factor', 'unpaid_leave_day_factor', 'half_day_factor',
    'deduct_late_minutes', 'standard_day_minutes', 'is_active',
])]
class PayrollCalculationRule extends Model
{
    /** @use HasFactory<PayrollCalculationRuleFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'requires_finalized_attendance' => false,
        'prorate_allowances' => false,
        'deduct_late_minutes' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            if ($rule->effective_to !== null && $rule->effective_to->lt($rule->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }
            foreach (['absence_day_factor', 'unpaid_leave_day_factor', 'half_day_factor'] as $factor) {
                $value = $rule->getAttribute($factor);
                if ($value !== null && (bccomp((string) $value, '0', 4) === -1 || bccomp((string) $value, '1', 4) === 1)) {
                    throw ValidationException::withMessages([$factor => 'Deduction factors must be between zero and one.']);
                }
            }
            if ($rule->deduct_late_minutes && ($rule->standard_day_minutes === null || $rule->standard_day_minutes < 1)) {
                throw ValidationException::withMessages(['standard_day_minutes' => 'Late deduction requires positive standard workday minutes.']);
            }
            $overlaps = self::query()->where('company_id', $rule->company_id)->where('is_active', true)
                ->whereKeyNot($rule)->whereDate('effective_from', '<=', $rule->effective_to ?? '9999-12-31')
                ->where(fn (Builder $query) => $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $rule->effective_from))->exists();
            if ($rule->is_active && $overlaps) {
                throw ValidationException::withMessages(['effective_from' => 'Active Payroll calculation rules cannot overlap.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query->where('is_active', true)->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_calculation_rules')
            ->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'requires_finalized_attendance' => 'boolean',
            'prorate_allowances' => 'boolean',
            'absence_day_factor' => 'decimal:4',
            'unpaid_leave_day_factor' => 'decimal:4',
            'half_day_factor' => 'decimal:4',
            'deduct_late_minutes' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
