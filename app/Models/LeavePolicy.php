<?php

namespace App\Models;

use Database\Factories\LeavePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'leave_type_id', 'name', 'effective_from', 'effective_to',
    'annual_units', 'maximum_carry_forward_units', 'allow_negative_balance',
    'allow_encashment', 'is_active',
])]
class LeavePolicy extends Model
{
    /** @use HasFactory<LeavePolicyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['allow_negative_balance' => false, 'allow_encashment' => false, 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (LeavePolicy $policy): void {
            if (! LeaveType::query()->whereKey($policy->leave_type_id)->where('company_id', $policy->company_id)->exists()) {
                throw ValidationException::withMessages(['leave_type_id' => 'The leave type must belong to the same company.']);
            }

            if ($policy->effective_to !== null && $policy->effective_to->lt($policy->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end date must be on or after the start date.']);
            }

            $overlaps = self::query()
                ->where('company_id', $policy->company_id)
                ->where('leave_type_id', $policy->leave_type_id)
                ->where('is_active', true)
                ->whereKeyNot($policy)
                ->whereDate('effective_from', '<=', $policy->effective_to ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $policy->effective_from))
                ->exists();

            if ($policy->is_active && $overlaps) {
                throw ValidationException::withMessages(['effective_from' => 'Active policies for a leave type cannot overlap.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('leave_policies')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'annual_units' => 'decimal:2',
            'maximum_carry_forward_units' => 'decimal:2',
            'allow_negative_balance' => 'boolean',
            'allow_encashment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
