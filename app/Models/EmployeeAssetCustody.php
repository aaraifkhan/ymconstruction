<?php

namespace App\Models;

use App\Enums\AssetCustodyExceptionType;
use App\Enums\EmployeeAssetCustodyStatus;
use Database\Factories\EmployeeAssetCustodyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'fixed_asset_id', 'employment_id', 'reference_number', 'status',
    'issued_on', 'due_on', 'returned_on', 'issued_condition', 'accessories',
    'issued_location', 'issue_notes', 'return_condition', 'return_notes',
    'exception_type', 'exception_notes', 'recovery_recommendation_amount',
    'recovery_recommendation_notes', 'prepared_by_id', 'issued_by_id', 'issued_at',
    'acknowledged_by_id', 'acknowledged_at', 'return_requested_by_id',
    'return_requested_at', 'returned_by_id', 'returned_at',
])]
#[Hidden([
    'accessories', 'issue_notes', 'return_notes', 'exception_notes',
    'recovery_recommendation_amount', 'recovery_recommendation_notes',
])]
class EmployeeAssetCustody extends Model
{
    /** @use HasFactory<EmployeeAssetCustodyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $custody): void {
            if (! Employment::query()->whereKey($custody->employment_id)
                ->where('company_id', $custody->company_id)->exists()
                || ! FixedAsset::query()->whereKey($custody->fixed_asset_id)
                    ->where('company_id', $custody->company_id)->exists()) {
                throw ValidationException::withMessages([
                    'employment_id' => 'Employment and Fixed Asset must belong to the custody company.',
                ]);
            }

            if ($custody->returned_on?->lt($custody->issued_on)) {
                throw ValidationException::withMessages(['returned_on' => 'Return date cannot precede issue date.']);
            }

            if ($custody->exists
                && EmployeeAssetCustodyStatus::from($custody->getRawOriginal('status')) !== EmployeeAssetCustodyStatus::Draft
                && $custody->isDirty(array_diff($custody->getFillable(), self::workflowFields()))) {
                throw ValidationException::withMessages(['status' => 'Issued custody terms are immutable.']);
            }
        });

        static::deleting(function (self $custody): void {
            if ($custody->status !== EmployeeAssetCustodyStatus::Draft || $custody->events()->exists()) {
                throw ValidationException::withMessages(['status' => 'Custody evidence cannot be deleted after issue.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmployeeAssetCustodyEvent::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_asset_custodies')
            ->logOnly([
                'company_id', 'fixed_asset_id', 'employment_id', 'reference_number',
                'status', 'issued_on', 'due_on', 'returned_on', 'issued_condition',
                'issued_location', 'return_condition', 'exception_type',
                ...self::workflowFields(),
            ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return list<string> */
    private static function workflowFields(): array
    {
        return [
            'reference_number', 'status', 'returned_on', 'return_condition', 'return_notes',
            'exception_type', 'exception_notes', 'recovery_recommendation_amount',
            'recovery_recommendation_notes', 'issued_by_id', 'issued_at',
            'acknowledged_by_id', 'acknowledged_at', 'return_requested_by_id',
            'return_requested_at', 'returned_by_id', 'returned_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => EmployeeAssetCustodyStatus::class,
            'exception_type' => AssetCustodyExceptionType::class,
            'issued_on' => 'date',
            'due_on' => 'date',
            'returned_on' => 'date',
            'accessories' => 'encrypted:array',
            'issue_notes' => 'encrypted',
            'return_notes' => 'encrypted',
            'exception_notes' => 'encrypted',
            'recovery_recommendation_amount' => 'encrypted',
            'recovery_recommendation_notes' => 'encrypted',
            'issued_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'return_requested_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }
}
