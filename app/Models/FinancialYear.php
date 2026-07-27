<?php

namespace App\Models;

use App\Enums\FinancialPeriodStatus;
use Database\Factories\FinancialYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'name', 'starts_on', 'ends_on', 'status'])]
class FinancialYear extends Model
{
    /** @use HasFactory<FinancialYearFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $year): void {
            if ($year->starts_on >= $year->ends_on) {
                throw ValidationException::withMessages(['ends_on' => 'Financial year end must be after its start.']);
            }
            $overlap = self::query()->where('company_id', $year->company_id)
                ->when($year->exists, fn ($query) => $query->whereKeyNot($year))
                ->where('starts_on', '<=', $year->ends_on)->where('ends_on', '>=', $year->starts_on)->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_on' => 'Financial years cannot overlap.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FinancialPeriod::class);
    }

    public function voucherSequences(): HasMany
    {
        return $this->hasMany(VoucherSequence::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function yearEndClosing(): HasOne
    {
        return $this->hasOne(YearEndClosing::class);
    }

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'status' => FinancialPeriodStatus::class];
    }
}
