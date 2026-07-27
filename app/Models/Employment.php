<?php

namespace App\Models;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use Database\Factories\EmploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    'employment_status',
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

    protected $attributes = [
        'employment_category' => EmploymentCategory::AdministrativeStaff->value,
        'employment_status' => EmploymentStatus::Probation->value,
        'working_days_per_week' => 6,
        'appointment_letter_issued' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (Employment $employment): void {
            if ($employment->isDirty('documents_verified_by_id')) {
                $employment->documents_verified_at = $employment->documents_verified_by_id === null ? null : now();
            }

            $employment->validateCompanyOwnedReferences();
            $employment->validateReportingLine();
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
                'employment_status',
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
            'employment_status' => EmploymentStatus::class,
            'documents_verified_at' => 'datetime',
            'appointment_letter_issued' => 'boolean',
        ];
    }

    private function validateCompanyOwnedReferences(): void
    {
        foreach (['department_id' => Department::class, 'designation_id' => Designation::class] as $field => $modelClass) {
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
}
