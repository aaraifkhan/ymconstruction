<?php

namespace App\Models;

use App\Enums\PerformanceAppraisalStatus;
use Database\Factories\PerformanceAppraisalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'appraisal_cycle_id', 'employment_id', 'reviewer_employment_id', 'status',
    'overall_score', 'outcome', 'employee_comments', 'source_checksum', 'created_by_id',
    'submitted_by_id', 'submitted_at', 'reviewed_by_id', 'reviewed_at', 'approved_by_id',
    'approved_at', 'acknowledged_by_id', 'acknowledged_at', 'rejected_by_id',
    'rejected_at', 'rejection_reason',
])]
#[Hidden(['overall_score', 'outcome', 'employee_comments'])]
class PerformanceAppraisal extends Model
{
    /** @use HasFactory<PerformanceAppraisalFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $appraisal): void {
            $cycle = AppraisalCycle::query()->whereKey($appraisal->appraisal_cycle_id)
                ->where('company_id', $appraisal->company_id)->first();
            $employmentIds = array_filter([$appraisal->employment_id, $appraisal->reviewer_employment_id]);
            if ($cycle === null || Employment::query()->whereIn('id', $employmentIds)
                ->where('company_id', $appraisal->company_id)->count() !== count($employmentIds)
                || (int) $appraisal->employment_id === (int) $appraisal->reviewer_employment_id) {
                throw ValidationException::withMessages([
                    'employment_id' => 'Cycle, employee, and reviewer must be distinct same-company records.',
                ]);
            }
        });
        static::updating(function (self $appraisal): void {
            $original = PerformanceAppraisalStatus::from($appraisal->getRawOriginal('status'));
            if (! in_array($original, [PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected], true)
                && $appraisal->isDirty(array_diff($appraisal->getFillable(), self::workflowFields()))) {
                throw ValidationException::withMessages(['status' => 'Submitted appraisal evidence is immutable.']);
            }
        });
        static::deleting(function (self $appraisal): void {
            if (! in_array($appraisal->status, [PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Submitted appraisals cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'appraisal_cycle_id');
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function reviewerEmployment(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'reviewer_employment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceAppraisalItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('performance_appraisals')->logOnly([
            'company_id', 'appraisal_cycle_id', 'employment_id', 'reviewer_employment_id', 'status',
            'source_checksum', ...self::workflowFields(),
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return list<string> */
    private static function workflowFields(): array
    {
        return [
            'status', 'overall_score', 'outcome', 'employee_comments',
            'submitted_by_id', 'submitted_at', 'reviewed_by_id', 'reviewed_at',
            'approved_by_id', 'approved_at', 'acknowledged_by_id', 'acknowledged_at',
            'rejected_by_id', 'rejected_at', 'rejection_reason',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => PerformanceAppraisalStatus::class,
            'overall_score' => 'encrypted',
            'outcome' => 'encrypted',
            'employee_comments' => 'encrypted',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
