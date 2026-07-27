<?php

namespace App\Models;

use Database\Factories\DesignationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'name', 'code', 'description', 'is_active'])]
class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('designations')
            ->logOnly(['company_id', 'name', 'code', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
