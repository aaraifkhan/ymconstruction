<?php

namespace App\Models;

use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\FinalSettlementStatus;
use App\Enums\PayrollRunStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryTransactionType;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use Database\Factories\TreasuryAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'treasury_transaction_id', 'company_id', 'allocatable_type', 'allocatable_id',
    'allocation_type', 'amount', 'reference_snapshot', 'due_date_snapshot',
])]
class TreasuryAllocation extends Model
{
    /** @use HasFactory<TreasuryAllocationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $allocation): void {
            $transaction = TreasuryTransaction::query()->find($allocation->treasury_transaction_id);
            if ($transaction === null || (int) $transaction->company_id !== (int) $allocation->company_id || ! $transaction->isEditable()) {
                throw ValidationException::withMessages(['treasury_transaction_id' => 'Allocations may only change on an editable same-company treasury transaction.']);
            }
            if (bccomp((string) $allocation->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Allocation amount must be positive.']);
            }
            if ($allocation->allocation_type === TreasuryAllocationType::VendorBill
                && $allocation->allocatable_type === VendorBill::class
                && $transaction->type === TreasuryTransactionType::Payment) {
                $openItem = VendorBill::query()->whereKey($allocation->allocatable_id)
                    ->where('company_id', $allocation->company_id)
                    ->where('type', VendorBillType::Invoice)->where('status', VendorBillStatus::Posted)->first();
                $partyId = $openItem?->vendor_id;
                $reference = $openItem?->vendor_bill_number;
            } elseif ($allocation->allocation_type === TreasuryAllocationType::CustomerInvoice
                && $allocation->allocatable_type === CustomerInvoice::class
                && $transaction->type === TreasuryTransactionType::Receipt) {
                $openItem = CustomerInvoice::query()->whereKey($allocation->allocatable_id)
                    ->where('company_id', $allocation->company_id)
                    ->where('type', CustomerInvoiceType::Invoice)->where('status', CustomerInvoiceStatus::Posted)->first();
                $partyId = $openItem?->customer_id;
                $reference = $openItem?->invoice_number;
            } elseif ($allocation->allocation_type === TreasuryAllocationType::PayrollEntry
                && $allocation->allocatable_type === PayrollEntry::class
                && $transaction->type === TreasuryTransactionType::Payment) {
                $openItem = PayrollEntry::query()->whereKey($allocation->allocatable_id)
                    ->where('company_id', $allocation->company_id)
                    ->whereHas('payrollRun', fn ($query) => $query
                        ->whereIn('status', [PayrollRunStatus::Approved, PayrollRunStatus::Paid, PayrollRunStatus::Locked])
                        ->whereNotNull('journal_entry_id')->whereNull('reversal_journal_entry_id'))
                    ->first();
                $partyId = null;
                $employmentId = $openItem?->employment_id;
                $reference = $openItem?->payrollRun?->reference_number;
            } elseif ($allocation->allocation_type === TreasuryAllocationType::FinalSettlement
                && $allocation->allocatable_type === FinalSettlement::class) {
                $openItem = FinalSettlement::query()->whereKey($allocation->allocatable_id)
                    ->where('company_id', $allocation->company_id)
                    ->whereIn('status', [
                        FinalSettlementStatus::Posted, FinalSettlementStatus::Settled,
                    ])->first();
                $expectedType = $openItem?->balance_direction === 'receivable'
                    ? TreasuryTransactionType::Receipt : TreasuryTransactionType::Payment;
                if ($transaction->type !== $expectedType) {
                    $openItem = null;
                }
                $partyId = null;
                $employmentId = $openItem?->employment_id;
                $reference = $openItem?->reference_number;
            } else {
                throw ValidationException::withMessages(['allocation_type' => 'Choose a Vendor Bill, Customer Invoice, posted Payroll Entry, or posted Final Settlement allocation.']);
            }
            $counterpartyMatches = isset($employmentId)
                ? $transaction->party_id === null && (int) $transaction->employment_id === (int) $employmentId
                : (int) $transaction->party_id === (int) $partyId;
            if ($openItem === null || ! $counterpartyMatches) {
                throw ValidationException::withMessages(['allocatable_id' => 'Choose a posted same-company open item for the transaction counterparty and direction.']);
            }
            $allocation->reference_snapshot = $reference;
            $allocation->due_date_snapshot = match (true) {
                $openItem instanceof PayrollEntry => $openItem->payrollRun->period_end,
                $openItem instanceof FinalSettlement => $openItem->cutoff_date,
                default => $openItem->due_date,
            };
        });

        static::deleting(function (self $allocation): void {
            if (! $allocation->treasuryTransaction()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['treasury_transaction_id' => 'Posted or in-review allocations are immutable.']);
            }
        });
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'allocation_type' => TreasuryAllocationType::class,
            'amount' => 'decimal:4',
            'due_date_snapshot' => 'date',
        ];
    }
}
