<?php

namespace App\Models;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'document_category_id',
    'documentable_type',
    'documentable_id',
    'title',
    'reference_number',
    'classification',
    'status',
    'issue_date',
    'expiry_date',
    'description',
    'metadata',
    'verified_by_id',
    'verified_at',
    'approved_by_id',
    'approved_at',
    'rejected_by_id',
    'rejected_at',
    'rejection_reason',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'classification' => DocumentClassification::Internal->value,
        'status' => DocumentStatus::Draft->value,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->ofMany('version', 'max');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin') || $user->can('ViewSensitive:Document')) {
            return $query;
        }

        return $query->where('classification', DocumentClassification::Internal->value);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date?->isPast() ?? false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('documents')
            ->logOnly([
                'company_id',
                'document_category_id',
                'documentable_type',
                'documentable_id',
                'title',
                'reference_number',
                'classification',
                'status',
                'issue_date',
                'expiry_date',
                'description',
                'metadata',
                'verified_by_id',
                'verified_at',
                'approved_by_id',
                'approved_at',
                'rejected_by_id',
                'rejected_at',
                'rejection_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'classification' => DocumentClassification::class,
            'status' => DocumentStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
