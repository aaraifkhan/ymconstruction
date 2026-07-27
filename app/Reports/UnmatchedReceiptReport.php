<?php

namespace App\Reports;

use App\Enums\GoodsReceiptStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\Company;
use App\Models\GoodsReceiptLine;
use App\Models\VendorBillReceiptAllocation;
use Illuminate\Support\Collection;

class UnmatchedReceiptReport
{
    /** @return Collection<int, array{receipt_line:GoodsReceiptLine, unmatched_quantity:string, unmatched_value:string}> */
    public function forCompany(Company $company): Collection
    {
        return GoodsReceiptLine::query()
            ->whereBelongsTo($company)
            ->whereHas('goodsReceipt', fn ($query) => $query->where('status', GoodsReceiptStatus::HandedOver->value))
            ->with(['goodsReceipt:id,goods_receipt_number,vendor_id,handed_over_at', 'item:id,code,name'])
            ->get()
            ->map(function (GoodsReceiptLine $line): array {
                $allocated = (string) VendorBillReceiptAllocation::query()
                    ->where('goods_receipt_line_id', $line->getKey())
                    ->whereHas('vendorBillLine.vendorBill', fn ($query) => $query
                        ->where('type', VendorBillType::Invoice->value)
                        ->whereNotIn('status', [
                            VendorBillStatus::Draft->value,
                            VendorBillStatus::Rejected->value,
                            VendorBillStatus::Reversed->value,
                        ]))
                    ->sum('quantity');
                $quantity = bcsub(
                    bcsub((string) $line->accepted_quantity, (string) $line->accepted_returned_quantity, 4),
                    $allocated,
                    4,
                );

                return [
                    'receipt_line' => $line,
                    'unmatched_quantity' => $quantity,
                    'unmatched_value' => bcmul($quantity, (string) $line->unit_cost_snapshot, 4),
                ];
            })
            ->filter(fn (array $row): bool => bccomp($row['unmatched_quantity'], '0', 4) === 1)
            ->values();
    }
}
