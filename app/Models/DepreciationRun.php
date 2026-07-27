<?php

namespace App\Models;

use App\Enums\AssetAccountingStatus;
use Database\Factories\DepreciationRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'financial_period_id', 'depreciation_date', 'reference_number', 'status', 'total_amount',
    'prepared_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
    'posted_by_id', 'posted_at', 'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at',
])]
class DepreciationRun extends Model
{
    /** @use HasFactory<DepreciationRunFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'draft', 'total_amount' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $run): void {
            $periodIsValid = FinancialPeriod::query()
                ->whereKey($run->financial_period_id)
                ->where('company_id', $run->company_id)
                ->whereDate('starts_on', '<=', $run->depreciation_date)
                ->whereDate('ends_on', '>=', $run->depreciation_date)
                ->exists();
            if (! $periodIsValid) {
                throw ValidationException::withMessages([
                    'financial_period_id' => 'Choose a same-company period containing the depreciation date.',
                ]);
            }
            if ($run->exists && self::query()->find($run->getKey())?->status !== AssetAccountingStatus::Draft) {
                $workflowFields = [
                    'status', 'reference_number', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
                    'posted_by_id', 'posted_at', 'journal_entry_id', 'reversal_journal_entry_id',
                    'reversed_by_id', 'reversed_at', 'updated_at',
                ];
                $protectedChanges = array_diff(array_keys($run->getDirty()), $workflowFields);
                if (in_array('total_amount', $protectedChanges, true)
                    && bccomp(
                        (string) $run->total_amount,
                        (string) self::query()->whereKey($run)->value('total_amount'),
                        4,
                    ) === 0) {
                    $protectedChanges = array_values(array_diff($protectedChanges, ['total_amount']));
                }
                if ($protectedChanges !== []) {
                    throw ValidationException::withMessages([
                        'status' => 'Submitted depreciation schedules are immutable: '.implode(', ', $protectedChanges).'.',
                    ]);
                }
            }
        });

        static::deleting(function (self $run): void {
            if ($run->status !== AssetAccountingStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft depreciation runs may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DepreciationRunLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    protected function casts(): array
    {
        return ['status' => AssetAccountingStatus::class, 'depreciation_date' => 'date', 'total_amount' => 'decimal:4', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'posted_at' => 'datetime', 'reversed_at' => 'datetime'];
    }
}
