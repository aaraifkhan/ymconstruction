<?php

namespace App\Models;

use App\Enums\ProcurementApprovalStatus;
use Database\Factories\ProcurementApprovalStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id',
    'approvable_type',
    'approvable_id',
    'procurement_approval_rule_id',
    'approval_round',
    'step_number',
    'name',
    'permission_name',
    'status',
    'decided_by_id',
    'decided_at',
    'decision_reason',
])]
class ProcurementApprovalStep extends Model
{
    /** @use HasFactory<ProcurementApprovalStepFactory> */
    use HasFactory;

    protected $attributes = ['status' => ProcurementApprovalStatus::Pending->value];

    protected static function booted(): void
    {
        static::updating(function (self $step): void {
            $allowedChanges = ['status', 'decided_by_id', 'decided_at', 'decision_reason', 'updated_at'];

            if (array_diff(array_keys($step->getDirty()), $allowedChanges) !== []) {
                throw ValidationException::withMessages(['status' => 'Approval-step configuration is an immutable document snapshot.']);
            }

            if ($step->getRawOriginal('status') !== ProcurementApprovalStatus::Pending->value) {
                throw ValidationException::withMessages(['status' => 'A decided approval step is immutable.']);
            }
        });

        static::deleting(fn () => throw ValidationException::withMessages([
            'status' => 'Approval-step evidence cannot be deleted.',
        ]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ProcurementApprovalRule::class, 'procurement_approval_rule_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    protected function casts(): array
    {
        return [
            'step_number' => 'integer',
            'approval_round' => 'integer',
            'status' => ProcurementApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
