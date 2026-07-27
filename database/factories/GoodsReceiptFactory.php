<?php

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_order_id' => function (): int {
                $order = PurchaseOrder::factory()->create();
                PurchaseOrderLine::factory()->create([
                    'purchase_order_id' => $order,
                    'company_id' => $order->company_id,
                ]);
                $order->update([
                    'status' => PurchaseOrderStatus::Ordered,
                    'ordered_at' => now(),
                ]);

                return $order->getKey();
            },
            'company_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->company_id,
            'vendor_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->vendor_id,
            'project_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->project_id,
            'project_site_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->project_site_id,
            'goods_receipt_number' => null,
            'delivery_reference' => fake()->bothify('DC-#####'),
            'delivery_date' => today(),
            'status' => GoodsReceiptStatus::Draft,
            'receiving_notes' => fake()->optional()->sentence(),
            'accepted_value' => 0,
        ];
    }
}
