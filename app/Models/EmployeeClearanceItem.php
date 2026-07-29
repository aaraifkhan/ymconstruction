<?php

namespace App\Models;

use App\Enums\ClearanceArea;
use App\Enums\ClearanceSourceKind;
use App\Enums\EmployeeClearanceItemStatus;
use Database\Factories\EmployeeClearanceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'employee_clearance_id', 'clearance_checklist_template_id',
    'source_kind', 'source_key', 'area', 'name', 'is_mandatory', 'status',
    'obligation_snapshot', 'decision_notes', 'recovery_recommendation_amount',
    'recovery_recommendation_notes', 'decided_by_id', 'decided_at',
])]
#[Hidden([
    'obligation_snapshot', 'decision_notes', 'recovery_recommendation_amount',
    'recovery_recommendation_notes',
])]
class EmployeeClearanceItem extends Model
{
    /** @use HasFactory<EmployeeClearanceItemFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'is_mandatory' => true];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if (! EmployeeClearance::query()->whereKey($item->employee_clearance_id)
                ->where('company_id', $item->company_id)->exists()
                || ($item->clearance_checklist_template_id !== null
                    && ! ClearanceChecklistTemplate::query()
                        ->whereKey($item->clearance_checklist_template_id)
                        ->where('company_id', $item->company_id)->exists())) {
                throw ValidationException::withMessages(['employee_clearance_id' => 'Clearance item references must belong to one company.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(EmployeeClearance::class, 'employee_clearance_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClearanceChecklistTemplate::class, 'clearance_checklist_template_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    protected function casts(): array
    {
        return [
            'source_kind' => ClearanceSourceKind::class,
            'area' => ClearanceArea::class,
            'status' => EmployeeClearanceItemStatus::class,
            'is_mandatory' => 'boolean',
            'obligation_snapshot' => 'encrypted:array',
            'decision_notes' => 'encrypted',
            'recovery_recommendation_amount' => 'encrypted',
            'recovery_recommendation_notes' => 'encrypted',
            'decided_at' => 'datetime',
        ];
    }
}
