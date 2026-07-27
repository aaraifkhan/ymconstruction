<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryStatus;
use Database\Factories\PayrollEntryFactory;
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
    'payroll_run_id', 'company_id', 'employment_id', 'employment_compensation_id', 'employee_name', 'employee_code',
    'designation', 'employment_category', 'period_days', 'payable_days', 'basic_salary', 'payable_basic',
    'house_travel_allowance', 'food_allowance', 'other_allowance', 'gross_salary', 'absence_deduction',
    'loan_advance_deduction', 'other_deduction', 'net_salary', 'bank_amount', 'cash_amount', 'currency_code', 'remarks',
])]
#[Hidden([
    'basic_salary', 'payable_basic', 'house_travel_allowance', 'food_allowance', 'other_allowance', 'gross_salary',
    'absence_deduction', 'loan_advance_deduction', 'other_deduction', 'net_salary', 'bank_amount', 'cash_amount', 'remarks',
])]
class PayrollEntry extends Model
{
    /** @use HasFactory<PayrollEntryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (PayrollEntry $entry): void {
            $run = PayrollRun::query()->find($entry->payroll_run_id);

            if ($run === null || $run->company_id !== $entry->company_id) {
                throw ValidationException::withMessages(['payroll_run_id' => 'Payroll run must belong to the entry company.']);
            }

            $employmentMatches = Employment::query()->whereKey($entry->employment_id)->where('company_id', $entry->company_id)->exists();

            if (! $employmentMatches) {
                throw ValidationException::withMessages(['employment_id' => 'Employment must belong to the entry company.']);
            }

            if (! in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true)) {
                throw ValidationException::withMessages(['payroll_entry' => 'Entries can only change while payroll is draft or rejected.']);
            }

            if ($entry->period_days < 1 || (float) $entry->payable_days < 0 || (float) $entry->payable_days > $entry->period_days) {
                throw ValidationException::withMessages(['payable_days' => 'Payable days must be within the payroll period.']);
            }

            foreach (['absence_deduction', 'loan_advance_deduction', 'other_deduction', 'bank_amount', 'cash_amount'] as $field) {
                if ((float) ($entry->getAttribute($field) ?? 0) < 0) {
                    throw ValidationException::withMessages([$field => 'Amounts cannot be negative.']);
                }
            }

            $entry->payable_basic = round((float) $entry->basic_salary * (float) $entry->payable_days / $entry->period_days, 2);
            $entry->gross_salary = round(
                (float) $entry->payable_basic + (float) $entry->house_travel_allowance
                + (float) $entry->food_allowance + (float) $entry->other_allowance,
                2,
            );
            $entry->net_salary = max(0, round(
                (float) $entry->gross_salary - (float) $entry->absence_deduction
                - (float) $entry->loan_advance_deduction - (float) $entry->other_deduction,
                2,
            ));
        });
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
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

    public function projectAllocations(): HasMany
    {
        return $this->hasMany(PayrollProjectAllocation::class);
    }

    public function treasuryAllocations(): HasMany
    {
        return $this->hasMany(TreasuryAllocation::class, 'allocatable_id')
            ->where('allocatable_type', self::class)
            ->where('allocation_type', TreasuryAllocationType::PayrollEntry);
    }

    public function expenseBasis(): string
    {
        return bcsub((string) $this->gross_salary, (string) $this->absence_deduction, 4);
    }

    public function postedOpenAmount(?int $excludingTransactionId = null): string
    {
        return $this->openAmountForStatuses([TreasuryStatus::Posted], $excludingTransactionId);
    }

    public function openAmount(?int $excludingTransactionId = null): string
    {
        return $this->openAmountForStatuses([TreasuryStatus::Approved, TreasuryStatus::Posted], $excludingTransactionId);
    }

    /** @param array<int, TreasuryStatus> $statuses */
    private function openAmountForStatuses(array $statuses, ?int $excludingTransactionId): string
    {
        $allocated = $this->treasuryAllocations()
            ->whereHas('treasuryTransaction', function ($query) use ($excludingTransactionId, $statuses): void {
                $query->whereIn('status', collect($statuses)->map->value->all());
                if ($excludingTransactionId !== null) {
                    $query->whereKeyNot($excludingTransactionId);
                }
            })->sum('amount');

        return max(0, (float) $this->net_salary - (float) $allocated) === 0.0
            ? '0.0000'
            : number_format(max(0, (float) $this->net_salary - (float) $allocated), 4, '.', '');
    }

    public function paymentMode(): string
    {
        return match (true) {
            (float) $this->bank_amount > 0 && (float) $this->cash_amount > 0 => 'Bank & Cash',
            (float) $this->bank_amount > 0 => 'Bank',
            (float) $this->cash_amount > 0 => 'Cash',
            default => 'Not allocated',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_entries')->logOnly([
            'payroll_run_id', 'company_id', 'employment_id', 'employment_compensation_id', 'employee_name',
            'employee_code', 'designation', 'employment_category', 'period_days', 'payable_days', 'currency_code',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'payable_days' => 'decimal:2',
            'basic_salary' => 'encrypted', 'payable_basic' => 'encrypted', 'house_travel_allowance' => 'encrypted',
            'food_allowance' => 'encrypted', 'other_allowance' => 'encrypted', 'gross_salary' => 'encrypted',
            'absence_deduction' => 'encrypted', 'loan_advance_deduction' => 'encrypted', 'other_deduction' => 'encrypted',
            'net_salary' => 'encrypted', 'bank_amount' => 'encrypted', 'cash_amount' => 'encrypted', 'remarks' => 'encrypted',
        ];
    }
}
