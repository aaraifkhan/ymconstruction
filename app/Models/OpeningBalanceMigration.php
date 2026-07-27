<?php

namespace App\Models;

use App\Enums\OpeningBalanceMigrationStatus;
use Database\Factories\OpeningBalanceMigrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'financial_year_id', 'financial_period_id', 'opening_date', 'idempotency_key',
    'source_filename', 'source_path', 'source_checksum', 'status', 'row_count', 'valid_row_count',
    'source_debit_total', 'source_credit_total', 'validation_summary', 'prepared_by_id',
    'validated_by_id', 'validated_at', 'imported_by_id', 'imported_at',
    'opening_balance_batch_id', 'reversed_by_id', 'reversed_at', 'reversal_reason',
    'reversal_entry_id',
])]
class OpeningBalanceMigration extends Model
{
    /** @use HasFactory<OpeningBalanceMigrationFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => OpeningBalanceMigrationStatus::Draft->value,
        'row_count' => 0,
        'valid_row_count' => 0,
        'source_debit_total' => 0,
        'source_credit_total' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $migration): void {
            if (! $migration->exists) {
                return;
            }
            $persisted = self::query()->whereKey($migration)->firstOrFail();
            $allowed = match ($persisted->status) {
                OpeningBalanceMigrationStatus::Draft => [
                    'source_path', 'status', 'row_count', 'valid_row_count', 'source_debit_total',
                    'source_credit_total', 'validation_summary', 'validated_by_id', 'validated_at', 'updated_at',
                ],
                OpeningBalanceMigrationStatus::Validated => [
                    'status', 'imported_by_id', 'imported_at', 'opening_balance_batch_id', 'updated_at',
                ],
                OpeningBalanceMigrationStatus::Imported => [
                    'status', 'reversed_by_id', 'reversed_at', 'reversal_reason', 'reversal_entry_id', 'updated_at',
                ],
                OpeningBalanceMigrationStatus::Failed, OpeningBalanceMigrationStatus::Reversed => [],
            };
            if (array_diff(array_keys($migration->getDirty()), $allowed) !== []) {
                throw ValidationException::withMessages(['status' => 'Migration evidence is immutable outside its controlled workflow.']);
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

    public function rows(): HasMany
    {
        return $this->hasMany(OpeningBalanceMigrationRow::class)->orderBy('source_row_number');
    }

    public function openingBalanceBatch(): BelongsTo
    {
        return $this->belongsTo(OpeningBalanceBatch::class);
    }

    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_entry_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opening_balance_migrations')->logOnly([
            'company_id', 'opening_date', 'source_filename', 'source_checksum', 'status',
            'row_count', 'valid_row_count', 'source_debit_total', 'source_credit_total',
            'validated_by_id', 'imported_by_id', 'opening_balance_batch_id', 'reversed_by_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'opening_date' => 'date',
            'status' => OpeningBalanceMigrationStatus::class,
            'source_debit_total' => 'decimal:4',
            'source_credit_total' => 'decimal:4',
            'validation_summary' => 'array',
            'validated_at' => 'datetime',
            'imported_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
