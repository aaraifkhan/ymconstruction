<?php

namespace App\Models;

use App\Enums\FinalSettlementStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryStatus;
use Database\Factories\FinalSettlementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'employment_separation_id', 'employee_clearance_id',
    'reference_number', 'cutoff_date', 'status', 'currency_code', 'earning_total',
    'recovery_total', 'net_amount', 'balance_direction', 'source_checksum', 'notes',
    'prepared_by_id', 'submitted_by_id', 'submitted_at', 'reviewed_by_id', 'reviewed_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
    'posted_by_id', 'posted_at', 'journal_entry_id', 'reversal_journal_entry_id',
    'reversed_by_id', 'reversed_at',
])]
#[Hidden(['earning_total', 'recovery_total', 'net_amount', 'source_checksum', 'notes'])]
class FinalSettlement extends Model
{
    /** @use HasFactory<FinalSettlementFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'currency_code' => 'PKR',
        'balance_direction' => 'payable',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $settlement): void {
            $separation = EmploymentSeparation::query()->whereKey($settlement->employment_separation_id)
                ->where('company_id', $settlement->company_id)
                ->where('employment_id', $settlement->employment_id)->first();
            $clearanceMatches = EmployeeClearance::query()->whereKey($settlement->employee_clearance_id)
                ->where('company_id', $settlement->company_id)
                ->where('employment_id', $settlement->employment_id)
                ->where('employment_separation_id', $settlement->employment_separation_id)->exists();
            if ($separation === null || ! $clearanceMatches
                || ! $settlement->cutoff_date->equalTo($separation->approved_last_working_date)
                || $settlement->currency_code !== 'PKR') {
                throw ValidationException::withMessages([
                    'employment_separation_id' => 'Settlement requires one same-company separation, clearance, and approved cutoff date.',
                ]);
            }

            if (! $settlement->exists) {
                return;
            }
            $persistedStatus = self::query()->whereKey($settlement)->value('status');
            if (in_array($persistedStatus, [
                FinalSettlementStatus::Draft->value, FinalSettlementStatus::Rejected->value,
            ], true)) {
                return;
            }
            $workflowFields = [
                'status', 'earning_total', 'recovery_total', 'net_amount', 'balance_direction',
                'source_checksum', 'submitted_by_id', 'submitted_at', 'reviewed_by_id',
                'reviewed_at', 'approved_by_id', 'approved_at', 'rejected_by_id',
                'rejected_at', 'rejection_reason', 'posted_by_id', 'posted_at',
                'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id',
                'reversed_at', 'updated_at',
            ];
            if (array_diff(array_keys($settlement->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted settlement inputs are immutable.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function separation(): BelongsTo
    {
        return $this->belongsTo(EmploymentSeparation::class, 'employment_separation_id');
    }

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(EmployeeClearance::class, 'employee_clearance_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinalSettlementLine::class)->orderBy('line_number');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function treasuryAllocations(): MorphMany
    {
        return $this->morphMany(TreasuryAllocation::class, 'allocatable')
            ->where('allocation_type', TreasuryAllocationType::FinalSettlement);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [FinalSettlementStatus::Draft, FinalSettlementStatus::Rejected], true);
    }

    public function signedNetAmount(): string
    {
        return $this->balance_direction === 'receivable'
            ? bcmul((string) $this->net_amount, '-1', 4)
            : (string) $this->net_amount;
    }

    public function postedOpenAmount(?int $excludingTransactionId = null): string
    {
        return $this->openAmountForStatuses([TreasuryStatus::Posted], $excludingTransactionId);
    }

    public function openAmount(?int $excludingTransactionId = null): string
    {
        return $this->openAmountForStatuses([TreasuryStatus::Approved, TreasuryStatus::Posted], $excludingTransactionId);
    }

    /** @param list<TreasuryStatus> $statuses */
    private function openAmountForStatuses(array $statuses, ?int $excludingTransactionId): string
    {
        $allocated = $this->treasuryAllocations()
            ->whereHas('treasuryTransaction', function ($query) use ($statuses, $excludingTransactionId): void {
                $query->whereIn('status', collect($statuses)->map->value->all());
                if ($excludingTransactionId !== null) {
                    $query->whereKeyNot($excludingTransactionId);
                }
            })->sum('amount');

        return bccomp((string) $allocated, (string) $this->net_amount, 4) >= 0
            ? '0.0000'
            : bcsub((string) $this->net_amount, (string) $allocated, 4);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('final_settlements')->logOnly([
            'company_id', 'employment_id', 'reference_number', 'cutoff_date', 'status',
            'balance_direction', 'prepared_by_id', 'submitted_by_id', 'reviewed_by_id',
            'approved_by_id', 'posted_by_id', 'journal_entry_id', 'reversal_journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => FinalSettlementStatus::class,
            'cutoff_date' => 'date',
            'earning_total' => 'encrypted',
            'recovery_total' => 'encrypted',
            'net_amount' => 'encrypted',
            'source_checksum' => 'encrypted',
            'notes' => 'encrypted',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
