<?php

namespace App\Models;

use App\Enums\FinancialPeriodStatus;
use Database\Factories\FinancialPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'financial_year_id', 'period_number', 'name', 'starts_on', 'ends_on', 'status', 'closed_by_id', 'closed_at', 'locked_by_id', 'locked_at', 'reopened_by_id', 'reopened_at', 'reopen_reason'])]
class FinancialPeriod extends Model
{
    /** @use HasFactory<FinancialPeriodFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $period): void {
            $year = $period->financialYear;
            if ($year === null || (int) $year->company_id !== (int) $period->company_id || $period->starts_on < $year->starts_on || $period->ends_on > $year->ends_on || $period->starts_on > $period->ends_on) {
                throw ValidationException::withMessages(['starts_on' => 'Period must be inside its company financial year.']);
            }
            $overlap = self::query()->where('company_id', $period->company_id)
                ->when($period->exists, fn ($query) => $query->whereKeyNot($period))
                ->where('starts_on', '<=', $period->ends_on)->where('ends_on', '>=', $period->starts_on)->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_on' => 'Financial periods cannot overlap.']);
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

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'ends_on' => 'date', 'status' => FinancialPeriodStatus::class,
            'closed_at' => 'datetime', 'locked_at' => 'datetime', 'reopened_at' => 'datetime',
        ];
    }
}
