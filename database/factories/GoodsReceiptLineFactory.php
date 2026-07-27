<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptLine>
 */
class GoodsReceiptLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'company_id' => fn (array $attributes): int => GoodsReceipt::query()
                ->findOrFail($attributes['goods_receipt_id'])->company_id,
            'purchase_order_line_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->where('purchase_order_id', GoodsReceipt::query()
                    ->findOrFail($attributes['goods_receipt_id'])->purchase_order_id)
                ->firstOrFail()
                ->getKey(),
            'line_number' => 1,
            'item_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->findOrFail($attributes['purchase_order_line_id'])->item_id,
            'unit_of_measure_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->findOrFail($attributes['purchase_order_line_id'])->unit_of_measure_id,
            'item_code_snapshot' => 'pending',
            'item_name_snapshot' => 'pending',
            'uom_snapshot' => 'pending',
            'received_quantity' => 10,
            'accepted_quantity' => 0,
            'rejected_quantity' => 0,
            'rejected_returned_quantity' => 0,
            'accepted_returned_quantity' => 0,
            'unit_cost_snapshot' => 0,
            'accepted_value' => 0,
        ];
    }
}
