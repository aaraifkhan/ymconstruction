<?php

namespace App\Models;

use App\Enums\YearEndClosingStatus;
use Database\Factories\YearEndClosingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'financial_year_id', 'idempotency_key', 'status', 'profit_or_loss',
    'calculation_checksum', 'calculation_snapshot', 'retained_earnings_account_id',
    'prepared_by_id', 'approved_by_id', 'approved_at', 'posted_by_id', 'posted_at',
    'journal_entry_id', 'reversed_by_id', 'reversed_at', 'reversal_reason', 'reversal_entry_id',
])]
class YearEndClosing extends Model
{
    /** @use HasFactory<YearEndClosingFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['status' => YearEndClosingStatus::Draft->value, 'profit_or_loss' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $closing): void {
            if (! $closing->exists) {
                return;
            }
            $persisted = self::query()->whereKey($closing)->firstOrFail();
            $allowed = match ($persisted->status) {
                YearEndClosingStatus::Draft => ['status', 'approved_by_id', 'approved_at', 'updated_at'],
                YearEndClosingStatus::Approved => ['status', 'posted_by_id', 'posted_at', 'journal_entry_id', 'updated_at'],
                YearEndClosingStatus::Posted => ['status', 'reversed_by_id', 'reversed_at', 'reversal_reason', 'reversal_entry_id', 'updated_at'],
                YearEndClosingStatus::Reversed => [],
            };
            if (array_diff(array_keys($closing->getDirty()), $allowed) !== []) {
                throw ValidationException::withMessages(['status' => 'Year-end closing evidence is immutable outside its controlled workflow.']);
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

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'retained_earnings_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_entry_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('year_end_closings')->logOnly([
            'company_id', 'financial_year_id', 'status', 'profit_or_loss', 'calculation_checksum',
            'retained_earnings_account_id', 'approved_by_id', 'posted_by_id', 'journal_entry_id',
            'reversed_by_id', 'reversal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => YearEndClosingStatus::class,
            'profit_or_loss' => 'decimal:4',
            'calculation_snapshot' => 'array',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
