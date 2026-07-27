<?php

namespace App\Models;

use App\Enums\BankStatementStatus;
use Database\Factories\BankStatementLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'bank_statement_id', 'company_id', 'company_bank_account_id', 'line_number',
    'transaction_date', 'value_date', 'description', 'bank_reference', 'debit',
    'credit', 'balance', 'fingerprint',
])]
class BankStatementLine extends Model
{
    /** @use HasFactory<BankStatementLineFactory> */
    use HasFactory;

    protected $attributes = ['debit' => 0, 'credit' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $statement = BankStatement::query()->find($line->bank_statement_id);
            if ($statement === null || (int) $statement->company_id !== (int) $line->company_id
                || (int) $statement->company_bank_account_id !== (int) $line->company_bank_account_id
                || $statement->status !== BankStatementStatus::Draft) {
                throw ValidationException::withMessages(['bank_statement_id' => 'Statement lines may only be imported into a draft same-company statement.']);
            }
            $debitPositive = bccomp((string) $line->debit, '0', 4) === 1;
            $creditPositive = bccomp((string) $line->credit, '0', 4) === 1;
            if ($debitPositive === $creditPositive) {
                throw ValidationException::withMessages(['debit' => 'Each bank line requires a positive debit or credit, never both.']);
            }
            if ($line->transaction_date->lt($statement->period_start) || $line->transaction_date->gt($statement->period_end)) {
                throw ValidationException::withMessages(['transaction_date' => 'Bank line date must fall within the statement period.']);
            }
        });

        static::deleting(function (self $line): void {
            if ($line->bankStatement()->firstOrFail()->status !== BankStatementStatus::Draft) {
                throw ValidationException::withMessages(['bank_statement_id' => 'Imported or locked statement lines are immutable.']);
            }
        });
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    public function reconciliationMatches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    public function statementAmount(): string
    {
        return bccomp((string) $this->debit, '0', 4) === 1
            ? (string) $this->debit
            : (string) $this->credit;
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'value_date' => 'date',
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'balance' => 'decimal:4',
            'line_number' => 'integer',
        ];
    }
}
