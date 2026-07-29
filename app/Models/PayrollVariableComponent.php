<?php

namespace App\Models;

use App\Enums\PayrollVariableComponentStatus;
use App\Enums\PayrollVariableComponentType;
use Database\Factories\PayrollVariableComponentFactory;
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
    'company_id', 'employment_id', 'project_id', 'type', 'status', 'earning_period_start',
    'earning_period_end', 'amount', 'currency_code', 'source_reference', 'source_checksum',
    'notes', 'created_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id',
    'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
])]
#[Hidden(['amount', 'notes'])]
class PayrollVariableComponent extends Model
{
    /** @use HasFactory<PayrollVariableComponentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'currency_code' => 'PKR'];

    protected static function booted(): void
    {
        static::saving(function (self $component): void {
            if (! Employment::query()->whereKey($component->employment_id)
                ->where('company_id', $component->company_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'Choose a same-company Employment.']);
            }
            if ($component->project_id !== null && ! Project::query()->whereKey($component->project_id)
                ->where('company_id', $component->company_id)->exists()) {
                throw ValidationException::withMessages(['project_id' => 'Choose a same-company Project.']);
            }
            if ($component->currency_code !== 'PKR' || bccomp((string) $component->amount, '0', 4) !== 1
                || $component->earning_period_end->lt($component->earning_period_start)) {
                throw ValidationException::withMessages(['amount' => 'Use a positive PKR amount and valid earning period.']);
            }
            $component->source_checksum = hash('sha256', json_encode([
                'company_id' => $component->company_id,
                'employment_id' => $component->employment_id,
                'project_id' => $component->project_id,
                'type' => $component->type instanceof PayrollVariableComponentType ? $component->type->value : $component->type,
                'period_start' => $component->earning_period_start->toDateString(),
                'period_end' => $component->earning_period_end->toDateString(),
                'amount' => number_format((float) $component->amount, 4, '.', ''),
                'source_reference' => $component->source_reference,
            ], JSON_THROW_ON_ERROR));
        });
        static::updating(function (self $component): void {
            $original = PayrollVariableComponentStatus::from($component->getRawOriginal('status'));
            if ($original === PayrollVariableComponentStatus::Approved
                || ($original === PayrollVariableComponentStatus::PendingApproval
                    && $component->isDirty(array_diff($component->getFillable(), [
                        'status', 'approved_by_id', 'approved_at', 'rejected_by_id',
                        'rejected_at', 'rejection_reason',
                    ])))) {
                throw ValidationException::withMessages(['status' => 'Submitted or approved variable Payroll sources are immutable.']);
            }
        });
        static::deleting(function (self $component): void {
            if (! in_array($component->status, [
                PayrollVariableComponentStatus::Draft,
                PayrollVariableComponentStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Submitted or approved variable Payroll sources cannot be deleted.']);
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payrollEntryComponents(): MorphMany
    {
        return $this->morphMany(PayrollEntryComponent::class, 'source');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_variable_components')->logOnly([
            'company_id', 'employment_id', 'project_id', 'type', 'status', 'earning_period_start',
            'earning_period_end', 'currency_code', 'source_reference', 'source_checksum',
            'created_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
            'rejected_by_id', 'rejected_at', 'rejection_reason',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => PayrollVariableComponentType::class,
            'status' => PayrollVariableComponentStatus::class,
            'earning_period_start' => 'date',
            'earning_period_end' => 'date',
            'amount' => 'encrypted',
            'notes' => 'encrypted',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
