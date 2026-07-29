<?php

namespace App\Models;

use App\Enums\CompensationStatus;
use App\Enums\EmploymentCategory;
use App\Enums\EmploymentMovementStatus;
use App\Enums\EmploymentMovementType;
use Database\Factories\EmploymentMovementRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'reference_number', 'type', 'status', 'effective_on',
    'target_department_id', 'target_designation_id', 'target_reporting_employment_id',
    'target_work_location_id', 'target_employment_category', 'employment_compensation_id',
    'reason', 'before_snapshot', 'target_snapshot', 'created_by_id', 'submitted_by_id',
    'submitted_at', 'approved_by_id', 'approved_at', 'applied_by_id', 'applied_at',
    'rejected_by_id', 'rejected_at', 'rejection_reason',
])]
#[Hidden(['reason', 'before_snapshot', 'target_snapshot'])]
class EmploymentMovementRequest extends Model
{
    /** @use HasFactory<EmploymentMovementRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $movement): void {
            $employment = Employment::query()->whereKey($movement->employment_id)
                ->where('company_id', $movement->company_id)->first();
            if ($employment === null || $movement->effective_on->lt($employment->joining_date)) {
                throw ValidationException::withMessages(['employment_id' => 'Choose a same-company Employment and valid effective date.']);
            }
            foreach ([
                'target_department_id' => Department::class,
                'target_designation_id' => Designation::class,
                'target_work_location_id' => WorkLocation::class,
                'target_reporting_employment_id' => Employment::class,
            ] as $field => $model) {
                $id = $movement->getAttribute($field);
                if ($id !== null && ! $model::query()->whereKey($id)
                    ->where('company_id', $movement->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'Every target must belong to the movement company.']);
                }
            }
            if ((int) $movement->target_reporting_employment_id === (int) $movement->employment_id) {
                throw ValidationException::withMessages(['target_reporting_employment_id' => 'An Employment cannot report to itself.']);
            }
            if ($movement->employment_compensation_id !== null
                && ! EmploymentCompensation::query()->whereKey($movement->employment_compensation_id)
                    ->where('company_id', $movement->company_id)
                    ->where('employment_id', $movement->employment_id)
                    ->where('status', CompensationStatus::Approved)
                    ->whereDate('effective_from', $movement->effective_on)->exists()) {
                throw ValidationException::withMessages([
                    'employment_compensation_id' => 'Compensation changes require a separately approved same-date compensation record.',
                ]);
            }
            if (collect([
                $movement->target_department_id, $movement->target_designation_id,
                $movement->target_reporting_employment_id, $movement->target_work_location_id,
                $movement->target_employment_category, $movement->employment_compensation_id,
            ])->filter(fn ($value) => $value !== null)->isEmpty()) {
                throw ValidationException::withMessages(['target_department_id' => 'Provide at least one movement target.']);
            }
        });
        static::updating(function (self $movement): void {
            $original = EmploymentMovementStatus::from($movement->getRawOriginal('status'));
            if (! in_array($original, [EmploymentMovementStatus::Draft, EmploymentMovementStatus::Rejected], true)
                && $movement->isDirty(array_diff($movement->getFillable(), self::workflowFields()))) {
                throw ValidationException::withMessages(['status' => 'Submitted movement terms are immutable.']);
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

    public function compensation(): BelongsTo
    {
        return $this->belongsTo(EmploymentCompensation::class, 'employment_compensation_id');
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function targetDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'target_designation_id');
    }

    public function targetReportingEmployment(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'target_reporting_employment_id');
    }

    public function targetWorkLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'target_work_location_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employment_movements')->logOnly([
            'company_id', 'employment_id', 'reference_number', 'type', 'status', 'effective_on',
            'target_department_id', 'target_designation_id', 'target_reporting_employment_id',
            'target_work_location_id', 'target_employment_category', 'employment_compensation_id',
            ...self::workflowFields(),
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return list<string> */
    private static function workflowFields(): array
    {
        return [
            'status', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
            'applied_by_id', 'applied_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
        ];
    }

    protected function casts(): array
    {
        return [
            'type' => EmploymentMovementType::class,
            'status' => EmploymentMovementStatus::class,
            'effective_on' => 'date',
            'target_employment_category' => EmploymentCategory::class,
            'reason' => 'encrypted',
            'before_snapshot' => 'encrypted:array',
            'target_snapshot' => 'encrypted:array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
