<?php

namespace App\Models;

use App\Enums\PartyRole;
use App\Enums\ProjectBudgetStatus;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'company_id',
    'client_party_id',
    'consultant_party_id',
    'code',
    'name',
    'location',
    'planned_start_date',
    'planned_completion_date',
    'actual_start_date',
    'actual_completion_date',
    'contract_value',
    'retention_terms',
    'mobilization_terms',
    'currency_code',
    'status',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'contract_value' => 0,
        'currency_code' => 'PKR',
        'status' => ProjectStatus::Planned->value,
    ];

    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            $client = Party::query()
                ->whereKey($project->client_party_id)
                ->where('company_id', $project->company_id)
                ->first();

            if ($client === null || ! $client->hasRole(PartyRole::Customer)) {
                throw ValidationException::withMessages([
                    'client_party_id' => 'Select a customer party belonging to the same company.',
                ]);
            }

            if ($project->consultant_party_id !== null) {
                $consultant = Party::query()
                    ->whereKey($project->consultant_party_id)
                    ->where('company_id', $project->company_id)
                    ->first();

                if ($consultant === null || ! $consultant->hasRole(PartyRole::Consultant)) {
                    throw ValidationException::withMessages([
                        'consultant_party_id' => 'Select a consultant party belonging to the same company.',
                    ]);
                }
            }

            $datePairs = [
                ['planned_start_date', 'planned_completion_date'],
                ['actual_start_date', 'actual_completion_date'],
            ];

            foreach ($datePairs as [$startField, $completionField]) {
                if ($project->{$startField} !== null
                    && $project->{$completionField} !== null
                    && $project->{$completionField}->lt($project->{$startField})) {
                    throw ValidationException::withMessages([
                        $completionField => 'The completion date cannot be before the corresponding start date.',
                    ]);
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'client_party_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'consultant_party_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(ProjectSite::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function customerInvoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function approvedBudget(): ?ProjectBudget
    {
        return $this->budgets()
            ->where('status', ProjectBudgetStatus::Approved)
            ->latest('version')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Active);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('projects')
            ->logOnly([
                'company_id',
                'client_party_id',
                'consultant_party_id',
                'code',
                'name',
                'location',
                'planned_start_date',
                'planned_completion_date',
                'actual_start_date',
                'actual_completion_date',
                'contract_value',
                'retention_terms',
                'mobilization_terms',
                'currency_code',
                'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'planned_start_date' => 'date',
            'planned_completion_date' => 'date',
            'actual_start_date' => 'date',
            'actual_completion_date' => 'date',
            'contract_value' => 'decimal:4',
            'retention_terms' => 'array',
            'mobilization_terms' => 'array',
            'status' => ProjectStatus::class,
        ];
    }
}
