<?php

namespace App\Models;

use App\Enums\CompensationStatus;
use Database\Factories\EmploymentCompensationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'employment_id',
    'status',
    'effective_from',
    'effective_to',
    'basic_salary',
    'house_travel_allowance',
    'fuel_allowance',
    'mobile_allowance',
    'internet_allowance',
    'food_allowance',
    'site_allowance',
    'project_allowance',
    'other_allowance',
    'currency_code',
    'notes',
    'created_by_id',
    'submitted_by_id',
    'submitted_at',
    'approved_by_id',
    'approved_at',
    'rejected_by_id',
    'rejected_at',
    'rejection_reason',
])]
#[Hidden([
    'basic_salary',
    'house_travel_allowance',
    'fuel_allowance',
    'mobile_allowance',
    'internet_allowance',
    'food_allowance',
    'site_allowance',
    'project_allowance',
    'other_allowance',
    'notes',
])]
class EmploymentCompensation extends Model
{
    /** @use HasFactory<EmploymentCompensationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'status' => CompensationStatus::Draft->value,
        'currency_code' => 'PKR',
    ];

    protected static function booted(): void
    {
        static::saving(function (EmploymentCompensation $compensation): void {
            $employmentBelongsToCompany = Employment::query()
                ->whereKey($compensation->employment_id)
                ->where('company_id', $compensation->company_id)
                ->exists();

            if (! $employmentBelongsToCompany) {
                throw ValidationException::withMessages([
                    'employment_id' => 'The employment must belong to the compensation company.',
                ]);
            }

            if (
                $compensation->effective_to !== null
                && $compensation->effective_to->lt($compensation->effective_from)
            ) {
                throw ValidationException::withMessages([
                    'effective_to' => 'The effective-to date must be on or after the effective-from date.',
                ]);
            }

            foreach (self::amountFields() as $field) {
                $amount = $compensation->getAttribute($field);

                if ($amount !== null && (! is_numeric($amount) || (float) $amount < 0)) {
                    throw ValidationException::withMessages([
                        $field => 'Compensation amounts must be zero or greater.',
                    ]);
                }
            }
        });

        static::updating(function (EmploymentCompensation $compensation): void {
            $originalStatus = CompensationStatus::from($compensation->getRawOriginal('status'));

            if (
                $originalStatus === CompensationStatus::Approved
                && $compensation->isDirty(array_diff($compensation->getFillable(), ['effective_to']))
            ) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Approved compensation values are immutable.',
                ]);
            }

            if (
                $originalStatus === CompensationStatus::PendingApproval
                && $compensation->isDirty(array_diff($compensation->getFillable(), [
                    'status',
                    'approved_by_id',
                    'approved_at',
                    'rejected_by_id',
                    'rejected_at',
                    'rejection_reason',
                ]))
            ) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Submitted compensation can only be approved or rejected.',
                ]);
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function grossSalary(): float
    {
        return collect(self::amountFields())
            ->sum(fn (string $field): float => (float) ($this->getAttribute($field) ?? 0));
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CompensationStatus::Approved);
    }

    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function formattedAmount(string $field): string
    {
        return $this->currency_code.' '.number_format((float) ($this->getAttribute($field) ?? 0), 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employment_compensations')
            ->logOnly([
                'company_id',
                'employment_id',
                'status',
                'effective_from',
                'effective_to',
                'currency_code',
                'created_by_id',
                'submitted_by_id',
                'submitted_at',
                'approved_by_id',
                'approved_at',
                'rejected_by_id',
                'rejected_at',
                'rejection_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return array<int, string>
     */
    public static function amountFields(): array
    {
        return [
            'basic_salary',
            'house_travel_allowance',
            'fuel_allowance',
            'mobile_allowance',
            'internet_allowance',
            'food_allowance',
            'site_allowance',
            'project_allowance',
            'other_allowance',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => CompensationStatus::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'basic_salary' => 'encrypted',
            'house_travel_allowance' => 'encrypted',
            'fuel_allowance' => 'encrypted',
            'mobile_allowance' => 'encrypted',
            'internet_allowance' => 'encrypted',
            'food_allowance' => 'encrypted',
            'site_allowance' => 'encrypted',
            'project_allowance' => 'encrypted',
            'other_allowance' => 'encrypted',
            'notes' => 'encrypted',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
