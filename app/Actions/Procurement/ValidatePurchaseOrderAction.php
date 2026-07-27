<?php

namespace App\Actions\Procurement;

use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

class ValidatePurchaseOrderAction
{
    /**
     * @return array{subtotal: string, tax_total: string, grand_total: string}
     */
    public function handle(PurchaseOrder $order): array
    {
        $lines = $order->lines()->orderBy('line_number')->lockForUpdate()->get();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Add at least one purchase-order line before submission.']);
        }

        $subtotal = '0.0000';
        $taxTotal = '0.0000';
        $grandTotal = '0.0000';

        foreach ($lines as $line) {
            if ($line->tax_code_id !== null
                && ! $line->taxCode()->activeOn($order->order_date->toDateString())->exists()) {
                throw ValidationException::withMessages([
                    'tax_code_id' => "Tax code on line {$line->line_number} is not active for the order date.",
                ]);
            }

            $subtotal = bcadd($subtotal, (string) $line->line_subtotal, 4);
            $taxTotal = bcadd($taxTotal, (string) $line->tax_amount, 4);
            $grandTotal = bcadd($grandTotal, (string) $line->line_total, 4);
        }

        return compact('subtotal', 'taxTotal', 'grandTotal');
    }
}
