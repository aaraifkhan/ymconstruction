<?php

namespace App\Models;

use App\Enums\CompanyModuleState;
use Database\Factories\CompanyModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'module_id', 'state', 'variant', 'settings'])]
class CompanyModule extends Model
{
    /** @use HasFactory<CompanyModuleFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'state' => CompanyModuleState::Inherit->value,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('company_modules')
            ->logOnly(['company_id', 'module_id', 'state', 'variant', 'settings'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'state' => CompanyModuleState::class,
            'settings' => 'array',
        ];
    }
}
