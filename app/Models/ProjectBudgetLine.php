<?php

namespace App\Models;

use App\Enums\ProjectBudgetStatus;
use Database\Factories\ProjectBudgetLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'project_budget_id',
    'cost_center_id',
    'item_category_id',
    'cost_code',
    'description',
    'amount',
    'sort_order',
])]
class ProjectBudgetLine extends Model
{
    /** @use HasFactory<ProjectBudgetLineFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['sort_order' => 0];

    protected static function booted(): void
    {
        static::saving(function (ProjectBudgetLine $line): void {
            if (bccomp((string) $line->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages([
                    'amount' => 'Budget line amount must be greater than zero.',
                ]);
            }

            $budget = ProjectBudget::query()
                ->whereKey($line->project_budget_id)
                ->where('company_id', $line->company_id)
                ->first();

            if ($budget === null) {
                throw ValidationException::withMessages([
                    'project_budget_id' => 'The selected budget must belong to the same company.',
                ]);
            }

            if ($budget->status !== ProjectBudgetStatus::Draft) {
                throw ValidationException::withMessages([
                    'project_budget_id' => 'Approved or superseded budget lines are immutable.',
                ]);
            }

            $relatedModels = [
                'cost_center_id' => [CostCenter::class, $line->cost_center_id],
                'item_category_id' => [ItemCategory::class, $line->item_category_id],
            ];

            foreach ($relatedModels as $field => [$model, $relatedId]) {
                if ($relatedId !== null && ! $model::query()->whereKey($relatedId)->where('company_id', $line->company_id)->exists()) {
                    throw ValidationException::withMessages([
                        $field => 'The selected record must belong to the same company.',
                    ]);
                }
            }
        });

        static::deleting(function (ProjectBudgetLine $line): void {
            if ($line->budget()->value('status') !== ProjectBudgetStatus::Draft) {
                throw ValidationException::withMessages([
                    'project_budget_id' => 'Approved or superseded budget lines are immutable.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_budget_lines')
            ->logOnly([
                'company_id',
                'project_budget_id',
                'cost_center_id',
                'item_category_id',
                'cost_code',
                'description',
                'amount',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }
}
