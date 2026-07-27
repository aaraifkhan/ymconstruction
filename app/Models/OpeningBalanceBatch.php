<?php

namespace App\Models;

use App\Enums\OpeningBalanceStatus;
use Database\Factories\OpeningBalanceBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'financial_year_id', 'financial_period_id', 'opening_date', 'source_name',
    'idempotency_key', 'status', 'debit_total', 'credit_total', 'prepared_by_id',
    'validated_by_id', 'validated_at', 'posted_by_id', 'posted_at', 'journal_entry_id', 'notes',
])]
class OpeningBalanceBatch extends Model
{
    /** @use HasFactory<OpeningBalanceBatchFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => OpeningBalanceStatus::Draft->value,
        'debit_total' => 0,
        'credit_total' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $batch): void {
            $period = FinancialPeriod::query()->whereKey($batch->financial_period_id)
                ->where('company_id', $batch->company_id)->where('financial_year_id', $batch->financial_year_id)->first();
            if ($period === null || $batch->opening_date < $period->starts_on || $batch->opening_date > $period->ends_on) {
                throw ValidationException::withMessages(['opening_date' => 'Opening date must be inside the selected company period.']);
            }

            if ($batch->exists) {
                $persistedStatus = self::query()->whereKey($batch)->value('status');
                if ($persistedStatus === OpeningBalanceStatus::Posted->value) {
                    throw ValidationException::withMessages(['status' => 'Posted opening-balance batches are immutable.']);
                }
                if ($persistedStatus === OpeningBalanceStatus::Validated->value
                    && ($batch->status !== OpeningBalanceStatus::Posted || array_diff(array_keys($batch->getDirty()), ['status', 'posted_by_id', 'posted_at', 'journal_entry_id', 'updated_at']) !== [])) {
                    throw ValidationException::withMessages(['status' => 'Validated batches may only transition to posted.']);
                }
            }
        });

        static::deleting(function (self $batch): void {
            if ($batch->status !== OpeningBalanceStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft opening-balance batches may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OpeningBalanceLine::class)->orderBy('line_number');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opening_balances')->logOnly([
            'company_id', 'opening_date', 'source_name', 'status', 'debit_total', 'credit_total',
            'prepared_by_id', 'validated_by_id', 'posted_by_id', 'journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'opening_date' => 'date',
            'status' => OpeningBalanceStatus::class,
            'debit_total' => 'decimal:4',
            'credit_total' => 'decimal:4',
            'validated_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }
}
