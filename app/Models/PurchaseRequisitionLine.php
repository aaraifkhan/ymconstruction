<?php

namespace App\Models;

use App\Enums\ProjectBudgetStatus;
use Database\Factories\PurchaseRequisitionLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'purchase_requisition_id', 'company_id', 'line_number', 'item_id', 'unit_of_measure_id',
    'project_budget_line_id', 'item_code_snapshot', 'item_name_snapshot', 'uom_snapshot',
    'quantity', 'estimated_rate', 'estimated_amount', 'ordered_quantity', 'specification',
])]
class PurchaseRequisitionLine extends Model
{
    /** @use HasFactory<PurchaseRequisitionLineFactory> */
    use HasFactory;

    protected $attributes = [
        'estimated_amount' => 0,
        'ordered_quantity' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $requisition = PurchaseRequisition::query()->find($line->purchase_requisition_id);
            if ($requisition === null || (int) $requisition->company_id !== (int) $line->company_id) {
                throw ValidationException::withMessages(['purchase_requisition_id' => 'The requisition line must belong to the same company.']);
            }

            $isOrderingQuantityUpdate = $line->exists
                && array_diff(array_keys($line->getDirty()), ['ordered_quantity', 'updated_at']) === [];

            if (! $requisition->isEditable() && ! $isOrderingQuantityUpdate) {
                throw ValidationException::withMessages(['purchase_requisition_id' => 'Submitted requisition lines are immutable.']);
            }

            if (bccomp((string) $line->quantity, '0', 4) !== 1
                || bccomp((string) $line->estimated_rate, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be positive and estimated rate cannot be negative.']);
            }

            if (bccomp((string) $line->ordered_quantity, '0', 4) === -1
                || bccomp((string) $line->ordered_quantity, (string) $line->quantity, 4) === 1) {
                throw ValidationException::withMessages(['ordered_quantity' => 'Ordered quantity must remain between zero and requested quantity.']);
            }

            $item = Item::query()->whereKey($line->item_id)->where('company_id', $line->company_id)->first();
            $unit = UnitOfMeasure::query()->whereKey($line->unit_of_measure_id)->where('company_id', $line->company_id)->first();
            if ($item === null || $unit === null || (int) $item->unit_of_measure_id !== (int) $unit->getKey()) {
                throw ValidationException::withMessages(['item_id' => 'The item and its unit of measure must belong to the requisition company.']);
            }

            if ($line->project_budget_line_id !== null) {
                $budgetLineMatches = ProjectBudgetLine::query()
                    ->whereKey($line->project_budget_line_id)
                    ->where('company_id', $line->company_id)
                    ->whereHas('budget', fn ($query) => $query
                        ->where('project_id', $requisition->project_id)
                        ->where('status', ProjectBudgetStatus::Approved))
                    ->exists();

                if (! $budgetLineMatches) {
                    throw ValidationException::withMessages(['project_budget_line_id' => 'Budget reference must be an approved line for the requisition project.']);
                }
            }

            if (! $isOrderingQuantityUpdate) {
                $line->item_code_snapshot = $item->code;
                $line->item_name_snapshot = $item->name;
                $line->uom_snapshot = $unit->symbol;
                $line->estimated_amount = bcmul((string) $line->quantity, (string) $line->estimated_rate, 4);
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->requisition()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['purchase_requisition_id' => 'Submitted requisition lines are immutable.']);
            }
        });
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function projectBudgetLine(): BelongsTo
    {
        return $this->belongsTo(ProjectBudgetLine::class);
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'estimated_rate' => 'decimal:4',
            'estimated_amount' => 'decimal:4',
            'ordered_quantity' => 'decimal:4',
        ];
    }
}
