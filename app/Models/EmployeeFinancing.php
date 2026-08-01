<?php

namespace App\Models;

use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeFinancingFactory;
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
    'company_id', 'employment_id', 'reference_number', 'type', 'sub_category', 'status', 'request_date',
    'purpose', 'principal_amount', 'finance_charge', 'total_repayable', 'installment_count',
    'first_due_date', 'installment_frequency', 'currency_code', 'notes', 'requested_by_id',
    'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at', 'rejected_by_id',
    'rejected_at', 'rejection_reason', 'cancelled_by_id', 'cancelled_at',
    'cancellation_reason', 'disbursed_at', 'settled_at',
])]
class EmployeeFinancing extends Model
{
    /** @use HasFactory<EmployeeFinancingFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'finance_charge' => 0,
        'installment_frequency' => 'monthly',
        'currency_code' => 'PKR',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $financing): void {
            if (! Employment::query()->whereKey($financing->employment_id)
                ->where('company_id', $financing->company_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'Choose a same-company Employment.']);
            }
            if ($financing->currency_code !== 'PKR'
                || bccomp((string) $financing->principal_amount, '0', 4) !== 1
                || bccomp((string) $financing->finance_charge, '0', 4) === -1
                || $financing->installment_count < 1) {
                throw ValidationException::withMessages(['principal_amount' => 'Use positive PKR principal, non-negative charge, and at least one installment.']);
            }
            $expectedTotal = bcadd((string) $financing->principal_amount, (string) $financing->finance_charge, 4);
            if (bccomp((string) $financing->total_repayable, $expectedTotal, 4) !== 0) {
                throw ValidationException::withMessages(['total_repayable' => 'Total repayable must equal principal plus the explicitly approved finance charge.']);
            }
            if ($financing->type === EmployeeFinancingType::Advance
                && bccomp((string) $financing->finance_charge, '0', 4) !== 0) {
                throw ValidationException::withMessages(['finance_charge' => 'Employee Advances cannot carry a finance charge.']);
            }

            if (! $financing->exists) {
                return;
            }
            $persistedStatus = self::query()->whereKey($financing)->value('status');
            if (in_array($persistedStatus, [EmployeeFinancingStatus::Draft->value, EmployeeFinancingStatus::Rejected->value], true)) {
                return;
            }
            $workflowFields = [
                'reference_number', 'status', 'submitted_by_id', 'submitted_at', 'approved_by_id',
                'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
                'cancelled_by_id', 'cancelled_at', 'cancellation_reason', 'disbursed_at',
                'settled_at', 'updated_at',
            ];
            if (array_diff(array_keys($financing->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted financing terms are immutable; use the controlled reschedule workflow.']);
            }
        });

        static::deleting(function (self $financing): void {
            if (! in_array($financing->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected financing may be archived.']);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(EmployeeFinancingInstallment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EmployeeFinancingTransaction::class);
    }

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function outstandingAmount(): string
    {
        $recovered = $this->transactions()->whereIn('type', [
            'treasury_recovery', 'payroll_recovery', 'waiver',
        ])->sum('total_amount');
        $reversed = $this->transactions()
            ->where('type', 'reversal')
            ->whereHas('reversalOf', fn ($query) => $query->whereNot('type', 'disbursement'))
            ->sum('total_amount');

        return bcsub((string) $this->total_repayable, bcsub((string) $recovered, (string) $reversed, 4), 4);
    }

    public function dueAmountOnOrBefore(CarbonInterface $date): string
    {
        return $this->installments()
            ->whereDate('due_date', '<=', $date->toDateString())
            ->whereNot('status', 'superseded')
            ->get()
            ->reduce(
                fn (string $due, EmployeeFinancingInstallment $installment): string => bcadd(
                    $due,
                    $installment->outstandingAmount(),
                    4,
                ),
                '0.0000',
            );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_financings')->logOnly([
            'company_id', 'employment_id', 'reference_number', 'type', 'status', 'request_date',
            'purpose', 'principal_amount', 'finance_charge', 'total_repayable',
            'installment_count', 'first_due_date', 'installment_frequency',
            'submitted_by_id', 'approved_by_id', 'rejected_by_id', 'cancelled_by_id',
            'disbursed_at', 'settled_at',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => EmployeeFinancingType::class,
            'status' => EmployeeFinancingStatus::class,
            'request_date' => 'date',
            'first_due_date' => 'date',
            'principal_amount' => 'decimal:4',
            'finance_charge' => 'decimal:4',
            'total_repayable' => 'decimal:4',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }
}
