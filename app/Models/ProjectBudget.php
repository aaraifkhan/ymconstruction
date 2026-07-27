<?php

namespace App\Models;

use App\Enums\ProjectBudgetStatus;
use Database\Factories\ProjectBudgetFactory;
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
    'company_id',
    'project_id',
    'version',
    'status',
    'currency_code',
    'total_amount',
    'notes',
    'prepared_by_id',
    'approved_by_id',
    'approved_at',
])]
class ProjectBudget extends Model
{
    /** @use HasFactory<ProjectBudgetFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'status' => ProjectBudgetStatus::Draft->value,
        'currency_code' => 'PKR',
        'total_amount' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (ProjectBudget $budget): void {
            if (! Project::query()->whereKey($budget->project_id)->where('company_id', $budget->company_id)->exists()) {
                throw ValidationException::withMessages([
                    'project_id' => 'The selected project must belong to the same company.',
                ]);
            }

            if ($budget->exists) {
                $persistedStatus = static::query()
                    ->whereKey($budget)
                    ->value('status');

                if ($persistedStatus !== ProjectBudgetStatus::Draft) {
                    throw ValidationException::withMessages([
                        'status' => 'Approved or superseded budgets are immutable.',
                    ]);
                }
            }
        });

        static::deleting(function (ProjectBudget $budget): void {
            if ($budget->status !== ProjectBudgetStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft budgets may be deleted.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ProjectBudgetStatus::Draft;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_budgets')
            ->logOnly([
                'company_id',
                'project_id',
                'version',
                'status',
                'currency_code',
                'total_amount',
                'notes',
                'prepared_by_id',
                'approved_by_id',
                'approved_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => ProjectBudgetStatus::class,
            'total_amount' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }
}
