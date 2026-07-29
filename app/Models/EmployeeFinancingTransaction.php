<?php

namespace App\Models;

use App\Enums\EmployeeFinancingTransactionType;
use Database\Factories\EmployeeFinancingTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'employee_financing_id', 'employee_financing_installment_id',
    'treasury_transaction_id', 'payroll_entry_id', 'journal_entry_id', 'reversal_of_id', 'type',
    'effective_date', 'principal_amount', 'finance_charge_amount', 'total_amount',
    'idempotency_key', 'reason', 'created_by_id',
])]
class EmployeeFinancingTransaction extends Model
{
    /** @use HasFactory<EmployeeFinancingTransactionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            if (! EmployeeFinancing::query()->whereKey($transaction->employee_financing_id)
                ->where('company_id', $transaction->company_id)->exists()) {
                throw ValidationException::withMessages(['employee_financing_id' => 'The financing must belong to the same company.']);
            }
            if (bccomp((string) $transaction->total_amount, bcadd((string) $transaction->principal_amount, (string) $transaction->finance_charge_amount, 4), 4) !== 0
                || bccomp((string) $transaction->total_amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['total_amount' => 'Financing transaction amounts must be positive and reconcile.']);
            }
        });
        static::updating(fn () => throw ValidationException::withMessages(['type' => 'Financing transactions are immutable.']));
        static::deleting(fn () => throw ValidationException::withMessages(['type' => 'Financing transactions cannot be deleted.']));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeFinancing(): BelongsTo
    {
        return $this->belongsTo(EmployeeFinancing::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(EmployeeFinancingInstallment::class, 'employee_financing_installment_id');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function payrollEntry(): BelongsTo
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected function casts(): array
    {
        return [
            'type' => EmployeeFinancingTransactionType::class,
            'effective_date' => 'date',
            'principal_amount' => 'decimal:4',
            'finance_charge_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }
}
