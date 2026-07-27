<?php

namespace App\Models;

use App\Enums\PartyRole;
use App\Enums\TreasuryStatus;
use App\Enums\VendorBillMatchStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use Carbon\CarbonInterface;
use Database\Factories\VendorBillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'purchase_order_id', 'original_vendor_bill_id', 'vendor_id',
    'counterparty_classification',
    'project_id', 'project_site_id', 'vendor_bill_number', 'vendor_invoice_number',
    'type', 'invoice_date', 'due_date', 'status', 'match_status', 'currency_code',
    'subtotal', 'tax_total', 'gross_total', 'deduction_total', 'net_payable',
    'match_snapshot', 'match_snapshot_hash', 'mismatch_reason',
    'mismatch_overridden_by_id', 'mismatch_overridden_at', 'notes', 'prepared_by_id',
    'submitted_by_id', 'submitted_at', 'reviewed_by_id', 'reviewed_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at',
    'rejection_reason', 'posted_by_id', 'posted_at', 'journal_entry_id',
    'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at',
])]
class VendorBill extends Model
{
    /** @use HasFactory<VendorBillFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'type' => VendorBillType::Invoice->value,
        'status' => VendorBillStatus::Draft->value,
        'currency_code' => 'PKR',
        'subtotal' => 0,
        'tax_total' => 0,
        'gross_total' => 0,
        'deduction_total' => 0,
        'net_payable' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $bill): void {
            $vendor = Party::query()
                ->whereKey($bill->vendor_id)
                ->where('company_id', $bill->company_id)
                ->where('is_active', true)
                ->first();
            if ($vendor === null || ! $vendor->hasRole(PartyRole::Vendor)) {
                throw ValidationException::withMessages(['vendor_id' => 'Choose an active same-company vendor.']);
            }

            $order = $bill->purchase_order_id === null ? null : PurchaseOrder::query()
                ->whereKey($bill->purchase_order_id)
                ->where('company_id', $bill->company_id)
                ->where('vendor_id', $bill->vendor_id)
                ->first();
            if ($bill->purchase_order_id !== null && $order === null) {
                throw ValidationException::withMessages(['purchase_order_id' => 'The purchase order must belong to this company and vendor.']);
            }

            if ($order !== null
                && ((int) $order->project_id !== (int) $bill->project_id
                    || (int) $order->project_site_id !== (int) $bill->project_site_id)) {
                throw ValidationException::withMessages(['project_id' => 'Bill project and site must match its purchase order.']);
            }

            if ($bill->currency_code !== 'PKR' || ($order !== null && $order->currency_code !== $bill->currency_code)) {
                throw ValidationException::withMessages(['currency_code' => 'Vendor Bills are PKR-only and must match the purchase order currency.']);
            }

            if ($bill->due_date->lt($bill->invoice_date)) {
                throw ValidationException::withMessages(['due_date' => 'Due date cannot be before invoice date.']);
            }

            if ($bill->type === VendorBillType::CreditNote && $bill->original_vendor_bill_id === null) {
                throw ValidationException::withMessages(['original_vendor_bill_id' => 'A Vendor Credit Note must reference its original posted bill.']);
            }

            if (! $bill->exists) {
                return;
            }

            $persistedStatus = self::query()->whereKey($bill)->value('status');
            if (in_array($persistedStatus, [VendorBillStatus::Draft->value, VendorBillStatus::Rejected->value], true)) {
                return;
            }

            $workflowFields = [
                'status', 'vendor_bill_number', 'subtotal', 'tax_total', 'gross_total',
                'deduction_total', 'net_payable', 'counterparty_classification',
                'match_status', 'match_snapshot',
                'match_snapshot_hash', 'mismatch_reason', 'mismatch_overridden_by_id',
                'mismatch_overridden_at', 'submitted_by_id', 'submitted_at', 'reviewed_by_id',
                'reviewed_at', 'approved_by_id', 'approved_at', 'rejected_by_id',
                'rejected_at', 'rejection_reason', 'posted_by_id', 'posted_at',
                'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id',
                'reversed_at', 'updated_at',
            ];
            if (array_diff(array_keys($bill->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted Vendor Bill details are immutable outside controlled workflow actions.']);
            }
        });

        static::deleting(function (self $bill): void {
            if (! $bill->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected Vendor Bills may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function originalVendorBill(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_vendor_bill_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class)->orderBy('line_number');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(VendorBillDeduction::class);
    }

    public function treasuryAllocations(): MorphMany
    {
        return $this->morphMany(TreasuryAllocation::class, 'allocatable');
    }

    public function settledAmount(?int $excludingTransactionId = null): string
    {
        $query = $this->treasuryAllocations()
            ->whereHas('treasuryTransaction', fn ($query) => $query
                ->whereIn('status', [TreasuryStatus::Approved->value, TreasuryStatus::Posted->value]));
        if ($excludingTransactionId !== null) {
            $query->where('treasury_transaction_id', '!=', $excludingTransactionId);
        }

        return (string) $query->sum('amount');
    }

    public function postedCreditAmount(): string
    {
        return (string) self::query()
            ->where('original_vendor_bill_id', $this->getKey())
            ->where('type', VendorBillType::CreditNote->value)
            ->where('status', VendorBillStatus::Posted->value)
            ->sum('net_payable');
    }

    public function openAmount(?int $excludingTransactionId = null): string
    {
        return bcsub(
            bcsub((string) $this->net_payable, $this->postedCreditAmount(), 4),
            $this->settledAmount($excludingTransactionId),
            4,
        );
    }

    public function postedOpenAmount(?CarbonInterface $asOf = null): string
    {
        $creditQuery = self::query()
            ->where('original_vendor_bill_id', $this->getKey())
            ->where('type', VendorBillType::CreditNote->value)
            ->where('status', VendorBillStatus::Posted->value);
        $settlementQuery = $this->treasuryAllocations()
            ->whereHas('treasuryTransaction', fn ($query) => $query
                ->where('status', TreasuryStatus::Posted->value)
                ->when($asOf !== null, fn ($query) => $query->whereDate('transaction_date', '<=', $asOf)));
        if ($asOf !== null) {
            $creditQuery->whereDate('invoice_date', '<=', $asOf);
        }

        return bcsub(
            bcsub((string) $this->net_payable, (string) $creditQuery->sum('net_payable'), 4),
            (string) $settlementQuery->sum('amount'),
            4,
        );
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [VendorBillStatus::Draft, VendorBillStatus::Rejected], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('vendor_bills')->logOnly([
            'company_id', 'purchase_order_id', 'original_vendor_bill_id', 'vendor_id',
            'counterparty_classification',
            'project_id', 'project_site_id', 'vendor_bill_number', 'vendor_invoice_number',
            'type', 'invoice_date', 'due_date', 'status', 'match_status', 'currency_code',
            'subtotal', 'tax_total', 'gross_total', 'deduction_total', 'net_payable',
            'match_snapshot_hash', 'mismatch_reason', 'mismatch_overridden_by_id',
            'prepared_by_id', 'reviewed_by_id', 'approved_by_id', 'posted_by_id',
            'journal_entry_id', 'reversal_journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => VendorBillType::class,
            'status' => VendorBillStatus::class,
            'match_status' => VendorBillMatchStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'gross_total' => 'decimal:4',
            'deduction_total' => 'decimal:4',
            'net_payable' => 'decimal:4',
            'match_snapshot' => 'array',
            'mismatch_overridden_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
