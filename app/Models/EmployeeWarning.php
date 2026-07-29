<?php

namespace App\Models;

use App\Enums\EmployeeWarningStatus;
use Database\Factories\EmployeeWarningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'warning_letter_template_id', 'reference_number', 'level',
    'incident_date', 'subject', 'body', 'response', 'closure_notes', 'status', 'created_by_id',
    'issued_by_id', 'issued_at', 'responded_by_id', 'responded_at', 'acknowledged_by_id',
    'acknowledged_at', 'closed_by_id', 'closed_at',
])]
#[Hidden(['body', 'response', 'closure_notes'])]
class EmployeeWarning extends Model
{
    /** @use HasFactory<EmployeeWarningFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $warning): void {
            $employmentMatches = Employment::query()->whereKey($warning->employment_id)
                ->where('company_id', $warning->company_id)->exists();
            $templateMatches = $warning->warning_letter_template_id === null
                || WarningLetterTemplate::query()->whereKey($warning->warning_letter_template_id)
                    ->where('company_id', $warning->company_id)->exists();
            if (! $employmentMatches || ! $templateMatches) {
                throw ValidationException::withMessages(['employment_id' => 'Employment and template must belong to the warning company.']);
            }
        });
        static::updating(function (self $warning): void {
            $original = EmployeeWarningStatus::from($warning->getRawOriginal('status'));
            if ($original !== EmployeeWarningStatus::Draft
                && $warning->isDirty(array_diff($warning->getFillable(), [
                    'status', 'response', 'closure_notes', 'responded_by_id', 'responded_at',
                    'acknowledged_by_id', 'acknowledged_at', 'closed_by_id', 'closed_at',
                ]))) {
                throw ValidationException::withMessages(['status' => 'Issued warning evidence is immutable.']);
            }
        });
        static::deleting(function (self $warning): void {
            if ($warning->status !== EmployeeWarningStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Issued warnings cannot be deleted.']);
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(WarningLetterTemplate::class, 'warning_letter_template_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_warnings')->logOnly([
            'company_id', 'employment_id', 'warning_letter_template_id', 'reference_number',
            'level', 'incident_date', 'subject', 'status', 'created_by_id', 'issued_by_id',
            'issued_at', 'responded_by_id', 'responded_at', 'acknowledged_by_id',
            'acknowledged_at', 'closed_by_id', 'closed_at',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'status' => EmployeeWarningStatus::class,
            'body' => 'encrypted',
            'response' => 'encrypted',
            'closure_notes' => 'encrypted',
            'issued_at' => 'datetime',
            'responded_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
