<?php

namespace App\Models;

use App\Enums\EmploymentAccessReviewStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\EmploymentSeparationType;
use Database\Factories\EmploymentSeparationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'reference_number', 'type', 'status', 'request_date',
    'proposed_last_working_date', 'approved_last_working_date', 'notice_days_required',
    'notice_days_served', 'reason', 'authority', 'protected_notes', 'handover_notes',
    'access_review_status', 'access_reviewed_by_id', 'access_reviewed_at', 'created_by_id',
    'submitted_by_id', 'submitted_at', 'accepted_by_id', 'accepted_at', 'approved_by_id',
    'approved_at', 'withdrawn_by_id', 'withdrawn_at', 'withdrawal_reason',
    'rejected_by_id', 'rejected_at', 'rejection_reason',
])]
#[Hidden(['reason', 'authority', 'protected_notes', 'handover_notes'])]
class EmploymentSeparation extends Model
{
    /** @use HasFactory<EmploymentSeparationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'access_review_status' => 'pending'];

    protected static function booted(): void
    {
        static::saving(function (self $separation): void {
            $employment = Employment::query()->whereKey($separation->employment_id)
                ->where('company_id', $separation->company_id)->first();
            $lastDate = $separation->approved_last_working_date ?? $separation->proposed_last_working_date;
            if ($employment === null || $separation->request_date->lt($employment->joining_date)
                || $lastDate->lt($employment->joining_date)) {
                throw ValidationException::withMessages(['employment_id' => 'Choose a same-company Employment and valid separation dates.']);
            }
            if ($separation->type === EmploymentSeparationType::Termination && blank($separation->authority)) {
                throw ValidationException::withMessages(['authority' => 'Termination requires the authorizing authority.']);
            }
        });
        static::updating(function (self $separation): void {
            $original = EmploymentSeparationStatus::from($separation->getRawOriginal('status'));
            if (! in_array($original, [EmploymentSeparationStatus::Draft, EmploymentSeparationStatus::Rejected], true)
                && $separation->isDirty(array_diff($separation->getFillable(), self::workflowFields()))) {
                throw ValidationException::withMessages(['status' => 'Submitted separation terms are immutable.']);
            }
        });
        static::deleting(function (self $separation): void {
            if (! in_array($separation->status, [EmploymentSeparationStatus::Draft, EmploymentSeparationStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Submitted separation evidence cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function clearance(): HasOne
    {
        return $this->hasOne(EmployeeClearance::class);
    }

    public function finalSettlement(): HasOne
    {
        return $this->hasOne(FinalSettlement::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employment_separations')->logOnly([
            'company_id', 'employment_id', 'reference_number', 'type', 'status', 'request_date',
            'proposed_last_working_date', 'approved_last_working_date', 'notice_days_required',
            'notice_days_served', 'access_review_status', ...self::workflowFields(),
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return list<string> */
    private static function workflowFields(): array
    {
        return [
            'status', 'approved_last_working_date', 'access_review_status',
            'access_reviewed_by_id', 'access_reviewed_at', 'submitted_by_id', 'submitted_at',
            'accepted_by_id', 'accepted_at', 'approved_by_id', 'approved_at',
            'withdrawn_by_id', 'withdrawn_at', 'withdrawal_reason',
            'rejected_by_id', 'rejected_at', 'rejection_reason',
        ];
    }

    protected function casts(): array
    {
        return [
            'type' => EmploymentSeparationType::class,
            'status' => EmploymentSeparationStatus::class,
            'access_review_status' => EmploymentAccessReviewStatus::class,
            'request_date' => 'date',
            'proposed_last_working_date' => 'date',
            'approved_last_working_date' => 'date',
            'reason' => 'encrypted',
            'authority' => 'encrypted',
            'protected_notes' => 'encrypted',
            'handover_notes' => 'encrypted',
            'access_reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'approved_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
