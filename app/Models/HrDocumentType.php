<?php

namespace App\Models;

use App\Enums\DocumentClassification;
use App\Enums\HrDocumentApplicability;
use App\Enums\HrDocumentTypeCode;
use Database\Factories\HrDocumentTypeFactory;
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
    'code',
    'name',
    'applicability',
    'default_classification',
    'requires_issue_date',
    'requires_expiry',
    'requires_verification',
    'requires_approval',
    'is_required',
    'is_active',
])]
class HrDocumentType extends Model
{
    /** @use HasFactory<HrDocumentTypeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'default_classification' => DocumentClassification::Restricted->value,
        'requires_issue_date' => false,
        'requires_expiry' => false,
        'requires_verification' => false,
        'requires_approval' => false,
        'is_required' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (HrDocumentType $type): void {
            if ($type->code->applicability() !== $type->applicability) {
                throw ValidationException::withMessages([
                    'applicability' => 'The applicability must match the controlled HR document type.',
                ]);
            }

            if ($type->exists
                && ($type->isDirty('code') || $type->isDirty('applicability'))
                && $type->documents()->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'A used HR document type cannot change its code or applicability.',
                ]);
            }
        });
    }

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
            ->useLogName('hr_document_types')
            ->logOnly([
                'company_id',
                'code',
                'name',
                'applicability',
                'default_classification',
                'requires_issue_date',
                'requires_expiry',
                'requires_verification',
                'requires_approval',
                'is_required',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'code' => HrDocumentTypeCode::class,
            'applicability' => HrDocumentApplicability::class,
            'default_classification' => DocumentClassification::class,
            'requires_issue_date' => 'boolean',
            'requires_expiry' => 'boolean',
            'requires_verification' => 'boolean',
            'requires_approval' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
