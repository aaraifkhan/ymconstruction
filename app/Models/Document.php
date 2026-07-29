<?php

namespace App\Models;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Enums\HrDocumentApplicability;
use App\Enums\HrDocumentTypeCode;
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
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'document_category_id',
    'hr_document_type_id',
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

    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            $document->validateHrDocumentType();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function hrDocumentType(): BelongsTo
    {
        return $this->belongsTo(HrDocumentType::class);
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
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if (! $user->can('ViewSensitive:Document')) {
            $query->where('classification', DocumentClassification::Internal->value);
        }

        if (! $user->can('ViewIdentity:EmployeeDocument')) {
            $query->whereDoesntHave(
                'hrDocumentType',
                fn (Builder $typeQuery): Builder => $typeQuery->whereIn('code', [
                    HrDocumentTypeCode::Cnic->value,
                    HrDocumentTypeCode::PoliceVerification->value,
                ]),
            );
        }

        if (! $user->can('ViewMedical:EmployeeDocument')) {
            $query->whereDoesntHave(
                'hrDocumentType',
                fn (Builder $typeQuery): Builder => $typeQuery->where(
                    'code',
                    HrDocumentTypeCode::MedicalCertificate->value,
                ),
            );
        }

        return $query;
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
                'hr_document_type_id',
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

    public function requiresVerification(): bool
    {
        return $this->category->requires_verification
            || ($this->hrDocumentType?->requires_verification ?? false);
    }

    public function requiresApproval(): bool
    {
        return $this->category->requires_approval
            || ($this->hrDocumentType?->requires_approval ?? false);
    }

    private function validateHrDocumentType(): void
    {
        if ($this->hr_document_type_id === null) {
            return;
        }

        $type = HrDocumentType::query()
            ->whereKey($this->hr_document_type_id)
            ->where('company_id', $this->company_id)
            ->first();

        if ($type === null) {
            throw ValidationException::withMessages([
                'hr_document_type_id' => 'The HR document type must belong to the same company.',
            ]);
        }

        $expectedDocumentableClass = match ($type->applicability) {
            HrDocumentApplicability::Employee => Employee::class,
            HrDocumentApplicability::Employment => Employment::class,
        };

        if ($this->documentable_type !== $expectedDocumentableClass) {
            throw ValidationException::withMessages([
                'hr_document_type_id' => 'The HR document type does not apply to the selected record.',
            ]);
        }

        if ($this->classification->sensitivityRank() < $type->default_classification->sensitivityRank()) {
            throw ValidationException::withMessages([
                'classification' => 'The sensitivity cannot be lower than the HR document type default.',
            ]);
        }

        if ($type->requires_issue_date && $this->issue_date === null) {
            throw ValidationException::withMessages([
                'issue_date' => 'An issue date is required for this HR document type.',
            ]);
        }

        if ($type->requires_expiry && $this->expiry_date === null) {
            throw ValidationException::withMessages([
                'expiry_date' => 'An expiry date is required for this HR document type.',
            ]);
        }
    }
}
