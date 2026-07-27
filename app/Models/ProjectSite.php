<?php

namespace App\Models;

use App\Enums\ProjectSiteType;
use Database\Factories\ProjectSiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'cost_center_id',
    'code',
    'name',
    'type',
    'location',
    'is_active',
])]
class ProjectSite extends Model
{
    /** @use HasFactory<ProjectSiteFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'type' => ProjectSiteType::Site->value,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (ProjectSite $site): void {
            if (! Project::query()->whereKey($site->project_id)->where('company_id', $site->company_id)->exists()) {
                throw ValidationException::withMessages([
                    'project_id' => 'The selected project must belong to the same company.',
                ]);
            }

            if ($site->cost_center_id !== null
                && ! CostCenter::query()->whereKey($site->cost_center_id)->where('company_id', $site->company_id)->exists()) {
                throw ValidationException::withMessages([
                    'cost_center_id' => 'The selected cost center must belong to the same company.',
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

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_sites')
            ->logOnly([
                'company_id',
                'project_id',
                'cost_center_id',
                'code',
                'name',
                'type',
                'location',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => ProjectSiteType::class,
            'is_active' => 'boolean',
        ];
    }
}
