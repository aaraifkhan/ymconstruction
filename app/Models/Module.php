<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['key', 'name', 'description', 'is_active', 'sort_order'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('modules')
            ->logOnly(['key', 'name', 'description', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
