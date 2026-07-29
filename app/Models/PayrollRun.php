<?php

namespace App\Models;

use App\Enums\JournalStatus;
use App\Enums\PayrollRunStatus;
use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'reference_number', 'period_start', 'period_end', 'status', 'currency_code', 'notes',
    'payroll_calculation_rule_id', 'generation_revision', 'source_checksum', 'generated_by_id', 'generated_at',
    'created_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
    'rejected_by_id', 'rejected_at', 'rejection_reason', 'paid_by_id', 'paid_at', 'locked_by_id', 'locked_at',
    'journal_entry_id', 'reversal_journal_entry_id', 'posted_by_id', 'posted_at', 'reversed_by_id', 'reversed_at',
])]
#[Hidden(['notes'])]
class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'currency_code' => 'PKR'];

    protected static function booted(): void
    {
        static::saving(function (PayrollRun $run): void {
            if ($run->period_end?->lt($run->period_start)) {
                throw ValidationException::withMessages(['period_end' => 'Period end must be on or after period start.']);
            }
        });

        static::updating(function (PayrollRun $run): void {
            $original = PayrollRunStatus::from($run->getRawOriginal('status'));

            if ($original === PayrollRunStatus::Locked) {
                throw ValidationException::withMessages(['payroll_run' => 'A locked payroll run is immutable.']);
            }

            $allowed = match ($original) {
                PayrollRunStatus::UnderReview => ['status', 'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason'],
                PayrollRunStatus::Approved => [
                    'status', 'paid_by_id', 'paid_at', 'journal_entry_id', 'reversal_journal_entry_id',
                    'posted_by_id', 'posted_at', 'reversed_by_id', 'reversed_at',
                ],
                PayrollRunStatus::Paid => ['status', 'locked_by_id', 'locked_at'],
                default => $run->getFillable(),
            };

            $disallowed = array_diff($run->getFillable(), $allowed);

            if ($disallowed !== [] && $run->isDirty($disallowed)) {
                throw ValidationException::withMessages(['payroll_run' => 'Payroll content cannot be changed in its current state.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function components(): HasManyThrough
    {
        return $this->hasManyThrough(PayrollEntryComponent::class, PayrollEntry::class);
    }

    public function calculationRule(): BelongsTo
    {
        return $this->belongsTo(PayrollCalculationRule::class, 'payroll_calculation_rule_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_id');
    }

    public function isPostedToAccounts(): bool
    {
        if ($this->journal_entry_id === null) {
            return false;
        }

        $journal = $this->relationLoaded('journalEntry')
            ? $this->journalEntry
            : $this->journalEntry()->first();

        return $journal?->status === JournalStatus::Posted;
    }

    public function settlementOpenAmount(): string
    {
        return number_format($this->entries->sum(
            fn (PayrollEntry $entry): float => (float) $entry->postedOpenAmount(),
        ), 4, '.', '');
    }

    public function total(string $field): float
    {
        return $this->entries->sum(fn (PayrollEntry $entry): float => (float) $entry->getAttribute($field));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_runs')->logOnly([
            'company_id', 'reference_number', 'period_start', 'period_end', 'status', 'currency_code',
            'payroll_calculation_rule_id', 'generation_revision', 'source_checksum', 'generated_by_id', 'generated_at',
            'created_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
            'rejected_by_id', 'rejected_at', 'rejection_reason', 'paid_by_id', 'paid_at', 'locked_by_id', 'locked_at',
            'journal_entry_id', 'reversal_journal_entry_id', 'posted_by_id', 'posted_at', 'reversed_by_id', 'reversed_at',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => PayrollRunStatus::class, 'period_start' => 'date', 'period_end' => 'date', 'notes' => 'encrypted',
            'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime',
            'paid_at' => 'datetime', 'locked_at' => 'datetime',
            'posted_at' => 'datetime', 'reversed_at' => 'datetime', 'generated_at' => 'datetime',
        ];
    }
}
