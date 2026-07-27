<?php

namespace App\Models;

use App\Enums\DocumentClassification;
use Database\Factories\DocumentCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'name',
    'slug',
    'description',
    'default_classification',
    'retention_days',
    'requires_expiry',
    'requires_verification',
    'requires_approval',
    'is_active',
])]
class DocumentCategory extends Model
{
    /** @use HasFactory<DocumentCategoryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'default_classification' => DocumentClassification::Internal->value,
        'requires_expiry' => false,
        'requires_verification' => false,
        'requires_approval' => false,
        'is_active' => true,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document_categories')
            ->logOnly([
                'company_id',
                'name',
                'slug',
                'description',
                'default_classification',
                'retention_days',
                'requires_expiry',
                'requires_verification',
                'requires_approval',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'default_classification' => DocumentClassification::class,
            'retention_days' => 'integer',
            'requires_expiry' => 'boolean',
            'requires_verification' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
