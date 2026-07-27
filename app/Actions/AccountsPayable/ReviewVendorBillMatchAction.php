<?php

namespace App\Actions\AccountsPayable;

use App\Enums\AccountingMappingKey;
use App\Enums\GoodsReceiptStatus;
use App\Enums\TaxCalculationMethod;
use App\Enums\VendorBillMatchStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\AccountingMapping;
use App\Models\ApMatchingSetting;
use App\Models\GoodsReceiptLine;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use App\Models\VendorBillReceiptAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewVendorBillMatchAction
{
    public function handle(
        VendorBill $bill,
        User $actor,
        bool $overrideMismatch = false,
        ?string $mismatchReason = null,
    ): VendorBill {
        Gate::forUser($actor)->authorize('reviewMatch', $bill);
        if ($overrideMismatch) {
            Gate::forUser($actor)->authorize('overrideMatch', $bill);
        }

        return DB::transaction(function () use ($actor, $bill, $mismatchReason, $overrideMismatch): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if ($bill->status !== VendorBillStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Only a submitted Vendor Bill may be reviewed.']);
            }
            if ((int) $bill->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['reviewed_by_id' => 'The Vendor Bill preparer cannot review their own bill.']);
            }

            if ($bill->type === VendorBillType::CreditNote) {
                return $this->reviewCreditNote($bill, $actor);
            }

            $settings = ApMatchingSetting::query()
                ->where('company_id', $bill->company_id)
                ->where('is_active', true)
                ->first() ?? new ApMatchingSetting;
            $grniAccountId = $this->mappedAccountId($bill->company_id, AccountingMappingKey::Grni);
            $exceptions = [];
            $snapshotLines = [];

            $lines = $bill->lines()
                ->with(['purchaseOrderLine', 'item', 'allocations.goodsReceiptLine.goodsReceipt'])
                ->orderBy('line_number')
                ->lockForUpdate()
                ->get();

            foreach ($lines as $line) {
                $orderLine = $line->purchaseOrderLine;
                if ($orderLine === null) {
                    throw ValidationException::withMessages(['purchase_order_line_id' => 'Invoice lines require a Purchase Order line.']);
                }

                $isStocked = (bool) $line->item?->track_inventory;
                $receiptValue = '0.0000';
                $allocatedQuantity = '0.0000';
                $quantityDeviation = '0.0000';

                if ($isStocked) {
                    if ($line->tax_calculation_method_snapshot === TaxCalculationMethod::Inclusive->value
                        && bccomp((string) $line->tax_amount, '0', 4) === 1) {
                        throw ValidationException::withMessages([
                            'tax_code_id' => 'Recoverable inclusive tax on stocked receipts requires a separate inventory-tax revaluation workflow and cannot be posted.',
                        ]);
                    }

                    foreach ($line->allocations as $allocation) {
                        $receiptLine = GoodsReceiptLine::query()
                            ->whereKey($allocation->goods_receipt_line_id)
                            ->lockForUpdate()
                            ->firstOrFail();
                        $receipt = $allocation->goodsReceiptLine->goodsReceipt;
                        if ($receipt->status !== GoodsReceiptStatus::HandedOver) {
                            throw ValidationException::withMessages(['allocations' => 'Only handed-over accepted receipt quantities may be invoiced.']);
                        }

                        $otherAllocated = (string) VendorBillReceiptAllocation::query()
                            ->where('goods_receipt_line_id', $receiptLine->getKey())
                            ->where('vendor_bill_line_id', '!=', $line->getKey())
                            ->whereHas('vendorBillLine.vendorBill', fn ($query) => $query
                                ->where('type', VendorBillType::Invoice->value)
                                ->whereNotIn('status', [
                                    VendorBillStatus::Draft->value,
                                    VendorBillStatus::Rejected->value,
                                    VendorBillStatus::Reversed->value,
                                ]))
                            ->sum('quantity');
                        $available = bcsub(
                            bcsub((string) $receiptLine->accepted_quantity, (string) $receiptLine->accepted_returned_quantity, 4),
                            $otherAllocated,
                            4,
                        );
                        if (bccomp((string) $allocation->quantity, $available, 4) === 1) {
                            throw ValidationException::withMessages([
                                'allocations' => 'A bill cannot consume more handed-over accepted quantity than remains available.',
                            ]);
                        }

                        $allocatedQuantity = bcadd($allocatedQuantity, (string) $allocation->quantity, 4);
                        $receiptValue = bcadd($receiptValue, (string) $allocation->receipt_value, 4);
                    }

                    if (bccomp($allocatedQuantity, (string) $line->quantity, 4) !== 0) {
                        throw ValidationException::withMessages([
                            'allocations' => 'Every stocked invoice quantity must be fully allocated to handed-over receipt lines.',
                        ]);
                    }
                    $line->update(['clearing_account_id' => $grniAccountId]);
                } else {
                    if ($line->clearing_account_id === null) {
                        throw ValidationException::withMessages([
                            'clearing_account_id' => 'Direct service or consumption lines require an explicit same-company expense/direct-cost account.',
                        ]);
                    }

                    $otherBilled = (string) VendorBillLine::query()
                        ->where('purchase_order_line_id', $orderLine->getKey())
                        ->whereKeyNot($line)
                        ->whereHas('vendorBill', fn ($query) => $query
                            ->where('type', VendorBillType::Invoice->value)
                            ->whereNotIn('status', [
                                VendorBillStatus::Draft->value,
                                VendorBillStatus::Rejected->value,
                                VendorBillStatus::Reversed->value,
                            ]))
                        ->sum('quantity');
                    $availableOrderQuantity = bcsub((string) $orderLine->quantity, $otherBilled, 4);
                    if (bccomp((string) $line->quantity, $availableOrderQuantity, 4) === 1) {
                        $quantityDeviation = $this->percentageDeviation((string) $line->quantity, $availableOrderQuantity);
                        if (bccomp($quantityDeviation, (string) $settings->quantity_tolerance_percentage, 4) === 1) {
                            $exceptions[] = "Line {$line->line_number} quantity differs by {$quantityDeviation}%.";
                        }
                    }
                }

                $rateDeviation = $this->percentageDeviation((string) $line->unit_rate, (string) $orderLine->unit_rate);
                $taxDeviation = $this->percentageDeviation((string) $line->tax_rate_snapshot, (string) $orderLine->tax_rate_snapshot);
                $priceVariance = $isStocked
                    ? bcsub((string) $line->line_subtotal, $receiptValue, 4)
                    : '0.0000';
                if ($isStocked && bccomp($priceVariance, '0', 4) !== 0 && $line->variance_account_id === null) {
                    throw ValidationException::withMessages([
                        'variance_account_id' => 'A same-company variance account is required when invoice value differs from receipt accrual.',
                    ]);
                }

                if (bccomp($rateDeviation, (string) $settings->rate_tolerance_percentage, 4) === 1) {
                    $exceptions[] = "Line {$line->line_number} rate differs by {$rateDeviation}%.";
                }
                if (bccomp($taxDeviation, (string) $settings->tax_tolerance_percentage, 4) === 1) {
                    $exceptions[] = "Line {$line->line_number} tax rate differs by {$taxDeviation}%.";
                }

                $line->update([
                    'receipt_value' => $receiptValue,
                    'price_variance' => $priceVariance,
                ]);
                $snapshotLines[] = [
                    'line_id' => $line->getKey(),
                    'po_line_id' => $orderLine->getKey(),
                    'quantity' => (string) $line->quantity,
                    'allocated_quantity' => $allocatedQuantity,
                    'quantity_deviation_percentage' => $quantityDeviation,
                    'invoice_rate' => (string) $line->unit_rate,
                    'po_rate' => (string) $orderLine->unit_rate,
                    'rate_deviation_percentage' => $rateDeviation,
                    'invoice_tax_rate' => (string) $line->tax_rate_snapshot,
                    'po_tax_rate' => (string) $orderLine->tax_rate_snapshot,
                    'tax_deviation_percentage' => $taxDeviation,
                    'receipt_value' => $receiptValue,
                    'price_variance' => $priceVariance,
                ];
            }

            if ($exceptions !== [] && ! $overrideMismatch) {
                throw ValidationException::withMessages(['match' => implode(' ', $exceptions)]);
            }
            if ($exceptions !== [] && blank($mismatchReason)) {
                throw ValidationException::withMessages(['mismatch_reason' => 'A reason is required for an authorized match exception.']);
            }

            $snapshot = [
                'settings' => [
                    'quantity_tolerance_percentage' => (string) $settings->quantity_tolerance_percentage,
                    'rate_tolerance_percentage' => (string) $settings->rate_tolerance_percentage,
                    'tax_tolerance_percentage' => (string) $settings->tax_tolerance_percentage,
                ],
                'exceptions' => $exceptions,
                'lines' => $snapshotLines,
            ];
            $encodedSnapshot = json_encode($snapshot, JSON_THROW_ON_ERROR);
            $hasAnyDeviation = collect($snapshotLines)->contains(
                fn (array $line): bool => bccomp($line['rate_deviation_percentage'], '0', 4) === 1
                    || bccomp($line['tax_deviation_percentage'], '0', 4) === 1
                    || bccomp($line['quantity_deviation_percentage'], '0', 4) === 1,
            );

            $bill->update([
                'status' => VendorBillStatus::Reviewed,
                'match_status' => $exceptions !== []
                    ? VendorBillMatchStatus::ExceptionApproved
                    : ($hasAnyDeviation ? VendorBillMatchStatus::WithinTolerance : VendorBillMatchStatus::Matched),
                'match_snapshot' => $snapshot,
                'match_snapshot_hash' => hash('sha256', $encodedSnapshot),
                'mismatch_reason' => $exceptions !== [] ? $mismatchReason : null,
                'mismatch_overridden_by_id' => $exceptions !== [] ? $actor->getKey() : null,
                'mismatch_overridden_at' => $exceptions !== [] ? now() : null,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('matched')
                ->withProperties([
                    'company_id' => $bill->company_id,
                    'match_status' => $bill->match_status,
                    'exception_count' => count($exceptions),
                    'match_snapshot_hash' => $bill->match_snapshot_hash,
                ])->log('reviewed PO, receipt, and Vendor Bill match');

            return $bill->refresh();
        }, attempts: 3);
    }

    private function reviewCreditNote(VendorBill $bill, User $actor): VendorBill
    {
        $original = $bill->originalVendorBill()->lockForUpdate()->firstOrFail();
        $postedCredits = (string) VendorBill::query()
            ->where('original_vendor_bill_id', $original->getKey())
            ->where('type', VendorBillType::CreditNote->value)
            ->whereIn('status', [
                VendorBillStatus::Posted->value,
                VendorBillStatus::Approved->value,
                VendorBillStatus::Reviewed->value,
            ])
            ->whereKeyNot($bill)
            ->sum('gross_total');
        if (bccomp(bcadd($postedCredits, (string) $bill->gross_total, 4), (string) $original->gross_total, 4) === 1) {
            throw ValidationException::withMessages(['gross_total' => 'Credit Notes cannot exceed the original Vendor Bill gross total.']);
        }

        foreach ($bill->lines()->with('originalVendorBillLine')->get() as $line) {
            $originalLine = $line->originalVendorBillLine;
            if ($originalLine === null || (int) $originalLine->vendor_bill_id !== (int) $original->getKey()) {
                throw ValidationException::withMessages(['original_vendor_bill_line_id' => 'Every Credit Note line must reference a line from the original Vendor Bill.']);
            }
            $line->update([
                'clearing_account_id' => $originalLine->clearing_account_id,
                'variance_account_id' => $originalLine->variance_account_id,
                'receipt_value' => bcdiv(
                    bcmul((string) $originalLine->receipt_value, (string) $line->quantity, 4),
                    (string) $originalLine->quantity,
                    4,
                ),
                'price_variance' => bcsub(
                    (string) $line->line_subtotal,
                    bcdiv(
                        bcmul((string) $originalLine->receipt_value, (string) $line->quantity, 4),
                        (string) $originalLine->quantity,
                        4,
                    ),
                    4,
                ),
            ]);
        }

        $snapshot = ['original_vendor_bill_id' => $original->getKey(), 'original_journal_entry_id' => $original->journal_entry_id];
        $bill->update([
            'status' => VendorBillStatus::Reviewed,
            'match_status' => VendorBillMatchStatus::NotApplicable,
            'match_snapshot' => $snapshot,
            'match_snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            'reviewed_by_id' => $actor->getKey(),
            'reviewed_at' => now(),
        ]);

        return $bill->refresh();
    }

    private function mappedAccountId(int $companyId, AccountingMappingKey $key): int
    {
        $accountId = AccountingMapping::query()
            ->where('company_id', $companyId)
            ->where('system_key', $key)
            ->where('is_active', true)
            ->value('account_id');

        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing active {$key->value} accounting mapping."]);
        }

        return (int) $accountId;
    }

    private function percentageDeviation(string $actual, string $expected): string
    {
        if (bccomp($actual, $expected, 4) === 0) {
            return '0.0000';
        }
        if (bccomp($expected, '0', 4) === 0) {
            return '100.0000';
        }

        $difference = bccomp($actual, $expected, 4) === 1
            ? bcsub($actual, $expected, 4)
            : bcsub($expected, $actual, 4);

        return bcdiv(bcmul($difference, '100.0000', 4), $expected, 4);
    }
}
