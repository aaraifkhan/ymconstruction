<?php

namespace App\Models;

use App\Actions\HR\AllocateEmployeeCodeAction;
use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\HrDocumentApplicability;
use Carbon\CarbonInterface;
use Database\Factories\EmploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'employee_id',
    'employee_code',
    'joining_date',
    'ending_date',
    'department_id',
    'designation_id',
    'reporting_to_employment_id',
    'employment_category',
    'employment_type',
    'employment_status',
    'probation_start_date',
    'probation_end_date',
    'confirmation_date',
    'notice_period_days',
    'work_location_id',
    'cost_center_id',
    'default_project_id',
    'payment_method',
    'work_start_time',
    'work_end_time',
    'working_days_per_week',
    'interviewed_by_id',
    'documents_verified_by_id',
    'documents_verified_at',
    'appointment_letter_issued',
    'hr_notes',
])]
class Employment extends Model
{
    /** @use HasFactory<EmploymentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    private ?CarbonInterface $approvedChangeEffectiveOn = null;

    private ?string $approvedChangeEventType = null;

    private ?int $approvedChangeActorId = null;

    protected $attributes = [
        'employment_category' => EmploymentCategory::AdministrativeStaff->value,
        'employment_type' => EmploymentType::Permanent->value,
        'employment_status' => EmploymentStatus::Probation->value,
        'working_days_per_week' => 6,
        'appointment_letter_issued' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (Employment $employment): void {
            if (blank($employment->employee_code)) {
                $employment->employee_code = app(AllocateEmployeeCodeAction::class)
                    ->handle((int) $employment->company_id);
            }
        });

        static::saving(function (Employment $employment): void {
            if ($employment->isDirty('documents_verified_by_id')) {
                $employment->documents_verified_at = $employment->documents_verified_by_id === null ? null : now();
            }

            $employment->validateCompanyOwnedReferences();
            $employment->validateLifecycle();
            $employment->validateReportingLine();
        });

        static::created(function (Employment $employment): void {
            $employment->recordHistory('created', null, self::historySnapshot($employment->getAttributes()));
        });

        static::updated(function (Employment $employment): void {
            $changedFields = array_values(array_intersect(
                array_keys($employment->getChanges()),
                self::historyFields(),
            ));

            if ($changedFields === []) {
                return;
            }

            $employment->recordHistory(
                $employment->approvedChangeEventType ?? 'updated',
                self::historySnapshot($employment->getRawOriginal()),
                self::historySnapshot($employment->getAttributes()),
                $changedFields,
            );
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function reportingEmployment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to_employment_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_to_employment_id');
    }

    public function interviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewed_by_id');
    }

    public function documentsVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_verified_by_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function joiningLetters(): HasMany
    {
        return $this->hasMany(JoiningLetter::class);
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(EmploymentCompensation::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function defaultProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'default_project_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(EmploymentChange::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendancePunches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class);
    }

    public function attendanceDeviceUserMappings(): HasMany
    {
        return $this->hasMany(AttendanceDeviceUserMapping::class);
    }

    public function employeeFinancings(): HasMany
    {
        return $this->hasMany(EmployeeFinancing::class);
    }

    public function payrollVariableComponents(): HasMany
    {
        return $this->hasMany(PayrollVariableComponent::class);
    }

    public function payrollEntryComponents(): HasMany
    {
        return $this->hasMany(PayrollEntryComponent::class);
    }

    public function performanceAppraisals(): HasMany
    {
        return $this->hasMany(PerformanceAppraisal::class);
    }

    public function employeeWarnings(): HasMany
    {
        return $this->hasMany(EmployeeWarning::class);
    }

    public function movementRequests(): HasMany
    {
        return $this->hasMany(EmploymentMovementRequest::class);
    }

    public function separations(): HasMany
    {
        return $this->hasMany(EmploymentSeparation::class);
    }

    public function assetCustodies(): HasMany
    {
        return $this->hasMany(EmployeeAssetCustody::class);
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(EmployeeClearance::class);
    }

    public function finalSettlements(): HasMany
    {
        return $this->hasMany(FinalSettlement::class);
    }

    public function recordApprovedChangeContext(string $eventType, CarbonInterface $effectiveOn, User $actor): void
    {
        $this->approvedChangeEventType = $eventType;
        $this->approvedChangeEffectiveOn = $effectiveOn;
        $this->approvedChangeActorId = $actor->getKey();
    }

    public function attendanceRawEvents(): HasMany
    {
        return $this->hasMany(AttendanceRawEvent::class);
    }

    public function attendanceMonthlySummaries(): HasMany
    {
        return $this->hasMany(AttendanceMonthlySummary::class);
    }

    public function leaveLedgerEntries(): HasMany
    {
        return $this->hasMany(LeaveLedgerEntry::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * @return Collection<int, HrDocumentType>
     */
    public function missingRequiredHrDocumentTypes(): Collection
    {
        return $this->company->hrDocumentTypes()
            ->where('applicability', HrDocumentApplicability::Employment)
            ->where('is_active', true)
            ->where('is_required', true)
            ->whereDoesntHave(
                'documents',
                fn ($query) => $query
                    ->where('documentable_type', self::class)
                    ->where('documentable_id', $this->getKey()),
            )
            ->orderBy('name')
            ->get();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employments')
            ->logOnly([
                'company_id',
                'employee_id',
                'employee_code',
                'joining_date',
                'ending_date',
                'department_id',
                'designation_id',
                'reporting_to_employment_id',
                'employment_category',
                'employment_type',
                'employment_status',
                'probation_start_date',
                'probation_end_date',
                'confirmation_date',
                'notice_period_days',
                'work_location_id',
                'work_start_time',
                'work_end_time',
                'working_days_per_week',
                'interviewed_by_id',
                'documents_verified_by_id',
                'documents_verified_at',
                'appointment_letter_issued',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'ending_date' => 'date',
            'employment_category' => EmploymentCategory::class,
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
            'probation_start_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'notice_period_days' => 'integer',
            'documents_verified_at' => 'datetime',
            'appointment_letter_issued' => 'boolean',
        ];
    }

    private function validateCompanyOwnedReferences(): void
    {
        foreach ([
            'department_id' => Department::class,
            'designation_id' => Designation::class,
            'work_location_id' => WorkLocation::class,
        ] as $field => $modelClass) {
            $recordId = $this->getAttribute($field);

            if ($recordId === null) {
                continue;
            }

            $belongsToCompany = $modelClass::query()
                ->whereKey($recordId)
                ->where('company_id', $this->company_id)
                ->exists();

            if (! $belongsToCompany) {
                throw ValidationException::withMessages([
                    $field => 'The selected record must belong to the same company as the employment.',
                ]);
            }
        }

        foreach (['interviewed_by_id', 'documents_verified_by_id'] as $field) {
            $userId = $this->getAttribute($field);

            if ($userId === null) {
                continue;
            }

            $user = User::query()->find($userId);
            $company = Company::query()->find($this->company_id);

            if ($user === null || $company === null || ! $user->canAccessTenant($company)) {
                throw ValidationException::withMessages([
                    $field => 'The selected user must have access to the employment company.',
                ]);
            }
        }

        if ($this->ending_date !== null && $this->joining_date !== null && $this->ending_date->lt($this->joining_date)) {
            throw ValidationException::withMessages([
                'ending_date' => 'The ending date must be on or after the joining date.',
            ]);
        }
    }

    private function validateLifecycle(): void
    {
        if ($this->probation_start_date !== null
            && $this->joining_date !== null
            && $this->probation_start_date->lt($this->joining_date)) {
            throw ValidationException::withMessages([
                'probation_start_date' => 'The probation start date cannot be before the joining date.',
            ]);
        }

        if ($this->probation_end_date !== null
            && ($this->probation_start_date === null || $this->probation_end_date->lt($this->probation_start_date))) {
            throw ValidationException::withMessages([
                'probation_end_date' => 'The probation end date requires a start date and must be on or after it.',
            ]);
        }

        if ($this->confirmation_date !== null
            && ($this->joining_date === null || $this->confirmation_date->lt($this->joining_date))) {
            throw ValidationException::withMessages([
                'confirmation_date' => 'The confirmation date must be on or after the joining date.',
            ]);
        }

        if ($this->confirmation_date !== null && $this->employment_status === EmploymentStatus::Probation) {
            throw ValidationException::withMessages([
                'employment_status' => 'A confirmed employment cannot remain in probation status.',
            ]);
        }

        if ($this->notice_period_days !== null && $this->notice_period_days < 1) {
            throw ValidationException::withMessages([
                'notice_period_days' => 'The notice period must be at least one calendar day.',
            ]);
        }

        if (in_array($this->employment_status, [
            EmploymentStatus::Resigned,
            EmploymentStatus::Terminated,
            EmploymentStatus::Ended,
        ], true) && $this->ending_date === null) {
            throw ValidationException::withMessages([
                'ending_date' => 'An ending date is required for resigned, terminated, or legacy ended employments.',
            ]);
        }
    }

    private function validateReportingLine(): void
    {
        if ($this->reporting_to_employment_id === null) {
            return;
        }

        $manager = self::withTrashed()->find($this->reporting_to_employment_id);

        if ($manager === null || $manager->company_id !== $this->company_id) {
            throw ValidationException::withMessages([
                'reporting_to_employment_id' => 'The reporting manager must have an employment in the same company.',
            ]);
        }

        $visitedEmploymentIds = [];

        while ($manager !== null) {
            if ($this->exists && $manager->is($this)) {
                throw ValidationException::withMessages([
                    'reporting_to_employment_id' => 'An employment cannot report to itself or one of its direct or indirect reports.',
                ]);
            }

            if (in_array($manager->getKey(), $visitedEmploymentIds, true)) {
                throw ValidationException::withMessages([
                    'reporting_to_employment_id' => 'The selected reporting line already contains a circular relationship.',
                ]);
            }

            $visitedEmploymentIds[] = $manager->getKey();
            $manager = $manager->reportingEmployment()->withTrashed()->first();
        }
    }

    /**
     * @return list<string>
     */
    private static function historyFields(): array
    {
        return [
            'employee_code',
            'joining_date',
            'ending_date',
            'department_id',
            'designation_id',
            'reporting_to_employment_id',
            'employment_category',
            'employment_type',
            'employment_status',
            'probation_start_date',
            'probation_end_date',
            'confirmation_date',
            'notice_period_days',
            'work_location_id',
            'work_start_time',
            'work_end_time',
            'working_days_per_week',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function historySnapshot(array $attributes): array
    {
        return collect(self::historyFields())
            ->mapWithKeys(fn (string $field): array => [$field => $attributes[$field] ?? null])
            ->all();
    }

    /**
     * @param  list<string>|null  $changedFields
     */
    private function recordHistory(
        string $eventType,
        ?array $beforeSnapshot,
        array $afterSnapshot,
        ?array $changedFields = null,
    ): void {
        $this->changes()->create([
            'company_id' => $this->company_id,
            'event_type' => $eventType,
            'effective_on' => $eventType === 'created'
                ? $this->joining_date
                : ($this->approvedChangeEffectiveOn ?? today()),
            'changed_fields' => $changedFields ?? self::historyFields(),
            'before_snapshot' => $beforeSnapshot,
            'after_snapshot' => $afterSnapshot,
            'recorded_by_id' => $this->approvedChangeActorId ?? auth()->id(),
        ]);
    }
}
