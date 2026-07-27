<?php

namespace App\Models;

use App\Enums\ProcurementDocumentType;
use Database\Factories\ProcurementApprovalRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

#[Fillable([
    'company_id',
    'document_type',
    'step_number',
    'name',
    'minimum_amount',
    'maximum_amount',
    'permission_name',
    'is_active',
])]
class ProcurementApprovalRule extends Model
{
    /** @use HasFactory<ProcurementApprovalRuleFactory> */
    use HasFactory;

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            if ($rule->minimum_amount !== null && bccomp((string) $rule->minimum_amount, '0', 4) === -1) {
                throw ValidationException::withMessages(['minimum_amount' => 'Minimum amount cannot be negative.']);
            }

            if ($rule->maximum_amount !== null
                && ($rule->minimum_amount === null || bccomp((string) $rule->maximum_amount, (string) $rule->minimum_amount, 4) === -1)) {
                throw ValidationException::withMessages(['maximum_amount' => 'Maximum amount must be greater than or equal to minimum amount.']);
            }

            if (! Permission::query()->where('name', $rule->permission_name)->where('guard_name', 'web')->exists()) {
                throw ValidationException::withMessages(['permission_name' => 'Select an existing approval permission.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ProcurementApprovalStep::class);
    }

    protected function casts(): array
    {
        return [
            'document_type' => ProcurementDocumentType::class,
            'step_number' => 'integer',
            'minimum_amount' => 'decimal:4',
            'maximum_amount' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
