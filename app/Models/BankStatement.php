<?php

namespace App\Models;

use App\Enums\BankStatementStatus;
use Database\Factories\BankStatementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'company_bank_account_id', 'period_start', 'period_end', 'opening_balance',
    'closing_balance', 'currency_code', 'status', 'source_file_name', 'source_sha256',
    'source_storage_path', 'imported_by_id', 'imported_at', 'locked_by_id', 'locked_at',
])]
class BankStatement extends Model
{
    /** @use HasFactory<BankStatementFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'currency_code' => 'PKR',
        'status' => BankStatementStatus::Draft->value,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $statement): void {
            $bank = CompanyBankAccount::query()->whereKey($statement->company_bank_account_id)
                ->where('company_id', $statement->company_id)->where('is_active', true)->first();
            if ($bank === null || $bank->currency_code !== 'PKR' || $statement->currency_code !== 'PKR') {
                throw ValidationException::withMessages(['company_bank_account_id' => 'Choose an active same-company PKR bank account.']);
            }
            if ($statement->period_end->lt($statement->period_start)) {
                throw ValidationException::withMessages(['period_end' => 'Statement period end cannot be before its start.']);
            }

            if ($statement->exists) {
                $persistedStatus = self::query()->whereKey($statement)->value('status');
                if ($persistedStatus === BankStatementStatus::Locked->value) {
                    throw ValidationException::withMessages(['status' => 'A locked bank statement is immutable.']);
                }
                if ($persistedStatus === BankStatementStatus::Imported->value) {
                    $allowed = ['status', 'locked_by_id', 'locked_at', 'updated_at'];
                    if (array_diff(array_keys($statement->getDirty()), $allowed) !== []) {
                        throw ValidationException::withMessages(['status' => 'Imported statement details are immutable.']);
                    }
                }
            }
        });

        static::deleting(function (self $statement): void {
            if ($statement->status !== BankStatementStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft bank statements may be deleted.']);
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

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->orderBy('line_number');
    }

    public function reconciliation(): HasOne
    {
        return $this->hasOne(BankReconciliation::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('bank_statements')->logOnly([
            'company_id', 'company_bank_account_id', 'period_start', 'period_end',
            'opening_balance', 'closing_balance', 'currency_code', 'status',
            'source_file_name', 'source_sha256', 'imported_by_id', 'locked_by_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => BankStatementStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'opening_balance' => 'decimal:4',
            'closing_balance' => 'decimal:4',
            'imported_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
