<?php

namespace App\Models;

use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\PartyRole;
use App\Enums\TreasuryStatus;
use Carbon\CarbonInterface;
use Database\Factories\CustomerInvoiceFactory;
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
    'company_id', 'customer_id', 'project_id', 'project_site_id', 'original_customer_invoice_id',
    'invoice_number', 'customer_reference', 'type', 'category', 'invoice_date', 'due_date',
    'certificate_number', 'certificate_date', 'contract_value_snapshot',
    'previous_certified_amount', 'work_value', 'variation_amount', 'subtotal', 'tax_total',
    'gross_total', 'retention_amount', 'wht_amount', 'mobilization_recovery_amount',
    'receivable_amount', 'currency_code', 'status', 'description', 'commercial_snapshot',
    'commercial_snapshot_hash', 'prepared_by_id', 'submitted_by_id', 'submitted_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
    'posted_by_id', 'posted_at', 'journal_entry_id', 'reversal_journal_entry_id',
    'reversed_by_id', 'reversed_at',
])]
class CustomerInvoice extends Model
{
    /** @use HasFactory<CustomerInvoiceFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'type' => CustomerInvoiceType::Invoice->value,
        'category' => CustomerInvoiceCategory::ServiceInvoice->value,
        'status' => CustomerInvoiceStatus::Draft->value,
        'currency_code' => 'PKR',
        'contract_value_snapshot' => 0,
        'previous_certified_amount' => 0,
        'work_value' => 0,
        'variation_amount' => 0,
        'subtotal' => 0,
        'tax_total' => 0,
        'gross_total' => 0,
        'retention_amount' => 0,
        'wht_amount' => 0,
        'mobilization_recovery_amount' => 0,
        'receivable_amount' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $invoice): void {
            $customer = Party::query()->whereKey($invoice->customer_id)
                ->where('company_id', $invoice->company_id)->where('is_active', true)->first();
            if ($customer === null || ! $customer->hasRole(PartyRole::Customer)) {
                throw ValidationException::withMessages(['customer_id' => 'Choose an active same-company Customer.']);
            }
            if ($invoice->currency_code !== 'PKR' || $invoice->due_date->lt($invoice->invoice_date)) {
                throw ValidationException::withMessages(['due_date' => 'Customer invoices require PKR and a due date on or after the invoice date.']);
            }

            $project = $invoice->project_id === null ? null : Project::query()
                ->whereKey($invoice->project_id)->where('company_id', $invoice->company_id)->first();
            if ($invoice->project_id !== null && ($project === null || (int) $project->client_party_id !== (int) $invoice->customer_id)) {
                throw ValidationException::withMessages(['project_id' => 'Project must belong to the company and selected Customer.']);
            }
            if ($invoice->project_site_id !== null && ! ProjectSite::query()->whereKey($invoice->project_site_id)
                ->where('company_id', $invoice->company_id)
                ->when($invoice->project_id !== null, fn ($query) => $query->where('project_id', $invoice->project_id))->exists()) {
                throw ValidationException::withMessages(['project_site_id' => 'Project Site must belong to the invoice company and Project.']);
            }
            if ($invoice->category === CustomerInvoiceCategory::RunningBill && $project === null) {
                throw ValidationException::withMessages(['project_id' => 'A construction Running Bill requires a Project.']);
            }

            if ($invoice->type === CustomerInvoiceType::CreditNote) {
                $original = self::query()->whereKey($invoice->original_customer_invoice_id)
                    ->where('company_id', $invoice->company_id)->where('customer_id', $invoice->customer_id)
                    ->where('type', CustomerInvoiceType::Invoice)->where('category', $invoice->category)->first();
                if ($original === null) {
                    throw ValidationException::withMessages(['original_customer_invoice_id' => 'Credit Note requires an original same-company invoice for the same Customer and category.']);
                }
            } elseif ($invoice->original_customer_invoice_id !== null) {
                throw ValidationException::withMessages(['original_customer_invoice_id' => 'Only a Credit Note references an original Customer Invoice.']);
            }

            if (! $invoice->exists) {
                return;
            }
            $persistedStatus = self::query()->whereKey($invoice)->value('status');
            if (is_string($persistedStatus)) {
                $persistedStatus = CustomerInvoiceStatus::from($persistedStatus);
            }
            if (in_array($persistedStatus, [CustomerInvoiceStatus::Draft, CustomerInvoiceStatus::Rejected], true)) {
                return;
            }
            $workflowFields = [
                'status', 'commercial_snapshot', 'commercial_snapshot_hash', 'submitted_by_id',
                'submitted_at', 'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at',
                'rejection_reason', 'posted_by_id', 'posted_at', 'journal_entry_id',
                'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at', 'updated_at',
            ];
            $blockedFields = array_diff(array_keys($invoice->getDirty()), $workflowFields);
            $decimalFields = [
                'contract_value_snapshot', 'previous_certified_amount', 'work_value',
                'variation_amount', 'subtotal', 'tax_total', 'gross_total',
                'retention_amount', 'wht_amount', 'mobilization_recovery_amount',
                'receivable_amount',
            ];
            $blockedFields = array_values(array_filter(
                $blockedFields,
                fn (string $field): bool => ! in_array($field, $decimalFields, true)
                    || bccomp(
                        (string) $invoice->getRawOriginal($field),
                        (string) $invoice->getAttribute($field),
                        4,
                    ) !== 0,
            ));
            if ($blockedFields !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted Customer Invoice details are immutable outside workflow actions.']);
            }
        });

        static::deleting(function (self $invoice): void {
            if (! $invoice->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected Customer Invoices may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function originalCustomerInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_customer_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'original_customer_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class)->orderBy('line_number');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CustomerInvoiceAdjustment::class);
    }

    public function treasuryAllocations(): MorphMany
    {
        return $this->morphMany(TreasuryAllocation::class, 'allocatable');
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
        return in_array($this->status, [CustomerInvoiceStatus::Draft, CustomerInvoiceStatus::Rejected], true);
    }

    public function creditedAmount(): string
    {
        return (string) $this->creditNotes()->where('status', CustomerInvoiceStatus::Posted)->sum('receivable_amount');
    }

    public function settledAmount(?int $excludingTransactionId = null): string
    {
        $query = $this->treasuryAllocations()->whereHas('treasuryTransaction', fn ($query) => $query
            ->whereIn('status', [TreasuryStatus::Approved->value, TreasuryStatus::Posted->value]));
        if ($excludingTransactionId !== null) {
            $query->where('treasury_transaction_id', '!=', $excludingTransactionId);
        }

        return (string) $query->sum('amount');
    }

    public function openAmount(?int $excludingTransactionId = null): string
    {
        return bcsub(bcsub((string) $this->receivable_amount, $this->creditedAmount(), 4), $this->settledAmount($excludingTransactionId), 4);
    }

    public function postedOpenAmount(?CarbonInterface $asOf = null): string
    {
        $credits = $this->creditNotes()->where('status', CustomerInvoiceStatus::Posted)
            ->when($asOf !== null, fn ($query) => $query->whereDate('invoice_date', '<=', $asOf));
        $receipts = $this->treasuryAllocations()->whereHas('treasuryTransaction', fn ($query) => $query
            ->where('status', TreasuryStatus::Posted)
            ->when($asOf !== null, fn ($query) => $query->whereDate('transaction_date', '<=', $asOf)));

        return bcsub(
            bcsub((string) $this->receivable_amount, (string) $credits->sum('receivable_amount'), 4),
            (string) $receipts->sum('amount'),
            4,
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('customer_invoices')->logOnly([
            'company_id', 'customer_id', 'project_id', 'project_site_id', 'invoice_number',
            'customer_reference', 'type', 'category', 'invoice_date', 'due_date',
            'certificate_number', 'certificate_date', 'subtotal', 'tax_total', 'gross_total',
            'retention_amount', 'wht_amount', 'mobilization_recovery_amount',
            'receivable_amount', 'status', 'prepared_by_id', 'submitted_by_id',
            'approved_by_id', 'rejected_by_id', 'rejection_reason', 'posted_by_id',
            'journal_entry_id', 'reversal_journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => CustomerInvoiceType::class,
            'category' => CustomerInvoiceCategory::class,
            'status' => CustomerInvoiceStatus::class,
            'invoice_date' => 'date', 'due_date' => 'date', 'certificate_date' => 'date',
            'contract_value_snapshot' => 'decimal:4', 'previous_certified_amount' => 'decimal:4',
            'work_value' => 'decimal:4', 'variation_amount' => 'decimal:4',
            'subtotal' => 'decimal:4', 'tax_total' => 'decimal:4', 'gross_total' => 'decimal:4',
            'retention_amount' => 'decimal:4', 'wht_amount' => 'decimal:4',
            'mobilization_recovery_amount' => 'decimal:4', 'receivable_amount' => 'decimal:4',
            'commercial_snapshot' => 'array', 'submitted_at' => 'datetime',
            'approved_at' => 'datetime', 'rejected_at' => 'datetime',
            'posted_at' => 'datetime', 'reversed_at' => 'datetime',
        ];
    }
}
