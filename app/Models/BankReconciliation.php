<?php

namespace App\Models;

use App\Enums\BankReconciliationStatus;
use Database\Factories\BankReconciliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'company_bank_account_id', 'bank_statement_id', 'period_start',
    'period_end', 'status', 'statement_closing_balance', 'book_closing_balance',
    'difference', 'prepared_by_id', 'closed_by_id', 'closed_at', 'reopened_by_id',
    'reopened_at', 'reopen_reason',
])]
class BankReconciliation extends Model
{
    /** @use HasFactory<BankReconciliationFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => BankReconciliationStatus::Draft->value,
        'statement_closing_balance' => 0,
        'book_closing_balance' => 0,
        'difference' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $reconciliation): void {
            $statement = BankStatement::query()->whereKey($reconciliation->bank_statement_id)
                ->where('company_id', $reconciliation->company_id)
                ->where('company_bank_account_id', $reconciliation->company_bank_account_id)
                ->first();
            if ($statement === null
                || ! $statement->period_start->equalTo($reconciliation->period_start)
                || ! $statement->period_end->equalTo($reconciliation->period_end)) {
                throw ValidationException::withMessages(['bank_statement_id' => 'Reconciliation must use its same-company bank statement period.']);
            }

            if ($reconciliation->exists) {
                $persistedStatus = self::query()->whereKey($reconciliation)->value('status');
                if ($persistedStatus === BankReconciliationStatus::Closed->value
                    && $reconciliation->status !== BankReconciliationStatus::Reopened) {
                    throw ValidationException::withMessages(['status' => 'Closed reconciliation is locked until an authorized reopen.']);
                }
            }
        });

        static::deleting(function (self $reconciliation): void {
            if ($reconciliation->status === BankReconciliationStatus::Closed) {
                throw ValidationException::withMessages(['status' => 'Closed reconciliation cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [BankReconciliationStatus::Draft, BankReconciliationStatus::Reopened], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('bank_reconciliations')->logOnly([
            'company_id', 'company_bank_account_id', 'bank_statement_id', 'period_start',
            'period_end', 'status', 'statement_closing_balance', 'book_closing_balance',
            'difference', 'prepared_by_id', 'closed_by_id', 'reopened_by_id', 'reopen_reason',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => BankReconciliationStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'statement_closing_balance' => 'decimal:4',
            'book_closing_balance' => 'decimal:4',
            'difference' => 'decimal:4',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }
}
