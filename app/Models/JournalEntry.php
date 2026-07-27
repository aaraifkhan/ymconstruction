<?php

namespace App\Models;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'financial_year_id', 'financial_period_id', 'voucher_type', 'voucher_number',
    'idempotency_key', 'status', 'transaction_date', 'reference', 'description', 'currency_code',
    'source_type', 'source_id', 'prepared_by_id', 'submitted_by_id', 'submitted_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
    'posted_by_id', 'posted_at', 'reverses_entry_id', 'reversed_by_entry_id', 'debit_total', 'credit_total',
])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => JournalStatus::Draft->value,
        'currency_code' => 'PKR',
        'debit_total' => 0,
        'credit_total' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $entry): void {
            if (blank($entry->idempotency_key)) {
                throw ValidationException::withMessages(['idempotency_key' => 'Every journal request requires an idempotency key.']);
            }

            $yearMatches = FinancialYear::query()->whereKey($entry->financial_year_id)->where('company_id', $entry->company_id)->exists();
            $periodMatches = FinancialPeriod::query()->whereKey($entry->financial_period_id)
                ->where('company_id', $entry->company_id)->where('financial_year_id', $entry->financial_year_id)->exists();

            if (! $yearMatches || ! $periodMatches) {
                throw ValidationException::withMessages(['financial_period_id' => 'The financial year and period must belong to the journal company.']);
            }

            if ($entry->source_type !== null || $entry->source_id !== null) {
                $source = $entry->source()->first();
                $sourceCompanyId = $source instanceof Company ? $source->getKey() : $source?->company_id;
                $sourceMatches = $source instanceof IntercompanyTransaction
                    ? in_array((int) $entry->company_id, [(int) $source->company_id, (int) $source->counterparty_company_id], true)
                    : $sourceCompanyId !== null && (int) $sourceCompanyId === (int) $entry->company_id;
                if ($source === null || ! $sourceMatches) {
                    throw ValidationException::withMessages(['source_id' => 'The accounting source must exist and belong to the journal company.']);
                }
            }

            if ($entry->exists) {
                $persisted = self::query()->whereKey($entry)->firstOrFail();
                $allowedPostedChanges = ['status', 'reversed_by_entry_id', 'updated_at'];
                $targetStatus = $entry->status;

                $validTransition = match ($persisted->status) {
                    JournalStatus::Draft => in_array($targetStatus, [JournalStatus::Draft, JournalStatus::Submitted], true)
                        || ($targetStatus === JournalStatus::Approved && (
                            in_array($entry->voucher_type, [VoucherType::OpeningBalance, VoucherType::Reversal], true)
                            || ($entry->voucher_type === VoucherType::InventoryAdjustment && $entry->source_type !== null)
                            || (in_array($entry->voucher_type, [VoucherType::Purchase, VoucherType::CreditNote], true)
                                && $entry->source_type === VendorBill::class)
                            || (in_array($entry->voucher_type, [VoucherType::Sales, VoucherType::CreditNote], true)
                                && $entry->source_type === CustomerInvoice::class)
                            || (in_array($entry->voucher_type, [VoucherType::Payment, VoucherType::Receipt, VoucherType::Contra], true)
                                && $entry->source_type === TreasuryTransaction::class)
                            || ($entry->voucher_type === VoucherType::Payroll
                                && $entry->source_type === PayrollRun::class)
                            || ($entry->voucher_type === VoucherType::Depreciation
                                && in_array($entry->source_type, [DepreciationRun::class, FixedAsset::class], true))
                            || ($entry->voucher_type === VoucherType::Journal
                                && $entry->source_type === AssetDisposal::class)
                            || ($entry->voucher_type === VoucherType::Journal
                                && $entry->source_type === FixedAsset::class)
                            || ($entry->voucher_type === VoucherType::Journal
                                && $entry->source_type === BankReconciliation::class)
                            || ($entry->voucher_type === VoucherType::InterCompany
                                && $entry->source_type === IntercompanyTransaction::class)
                            || ($entry->voucher_type === VoucherType::Journal
                                && $entry->source_type === YearEndClosing::class)
                        )),
                    JournalStatus::Rejected => in_array($targetStatus, [JournalStatus::Rejected, JournalStatus::Submitted], true),
                    JournalStatus::Submitted => in_array($targetStatus, [JournalStatus::Approved, JournalStatus::Rejected], true),
                    JournalStatus::Approved => in_array($targetStatus, [JournalStatus::Posted, JournalStatus::Rejected], true),
                    JournalStatus::Posted => $targetStatus === JournalStatus::Reversed,
                    JournalStatus::Reversed => false,
                };

                if (! $validTransition) {
                    throw ValidationException::withMessages(['status' => 'Invalid journal workflow transition.']);
                }

                if (in_array($persisted->status, [JournalStatus::Submitted, JournalStatus::Approved], true) && ! $entry->isDirty('status')) {
                    throw ValidationException::withMessages(['status' => 'In-review and approved journal headers are immutable outside workflow actions.']);
                }

                if ($persisted->status === JournalStatus::Posted
                    && ($entry->status !== JournalStatus::Reversed || array_diff(array_keys($entry->getDirty()), $allowedPostedChanges) !== [])) {
                    throw ValidationException::withMessages(['status' => 'Posted journals are immutable and may only be linked to one reversal.']);
                }

                if ($persisted->status === JournalStatus::Reversed) {
                    throw ValidationException::withMessages(['status' => 'Reversed journals are immutable.']);
                }
            }
        });

        static::deleting(function (self $entry): void {
            if (! in_array($entry->status, [JournalStatus::Draft, JournalStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected journals may be deleted.']);
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
        return $this->hasMany(JournalLine::class)->orderBy('line_number');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
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

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function reversesEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_entry_id');
    }

    public function reversedByEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_entry_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [JournalStatus::Draft, JournalStatus::Rejected], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('journal_entries')->logOnly([
            'company_id', 'financial_period_id', 'voucher_type', 'voucher_number', 'status',
            'transaction_date', 'reference', 'description', 'prepared_by_id', 'submitted_by_id',
            'approved_by_id', 'rejected_by_id', 'rejection_reason', 'posted_by_id',
            'reverses_entry_id', 'reversed_by_entry_id', 'debit_total', 'credit_total',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'voucher_type' => VoucherType::class,
            'status' => JournalStatus::class,
            'transaction_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'posted_at' => 'datetime',
            'debit_total' => 'decimal:4',
            'credit_total' => 'decimal:4',
        ];
    }
}
