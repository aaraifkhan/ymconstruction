<?php

namespace App\Models;

use Database\Factories\PerformanceKpiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'code', 'name', 'description', 'measurement_unit', 'is_active'])]
class PerformanceKpi extends Model
{
    /** @use HasFactory<PerformanceKpiFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appraisalItems(): HasMany
    {
        return $this->hasMany(PerformanceAppraisalItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('performance_kpis')
            ->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
