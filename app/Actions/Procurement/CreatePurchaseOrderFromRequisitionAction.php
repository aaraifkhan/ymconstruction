<?php

namespace App\Actions\Procurement;

use App\Enums\PartyRole;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrderFromRequisitionAction
{
    /**
     * @param  array<int, array{purchase_requisition_line_id: int, quantity: string|int|float, unit_rate?: string|int|float, tax_code_id?: int|null}>  $lineSelections
     */
    public function handle(
        PurchaseRequisition $requisition,
        Party $vendor,
        array $lineSelections,
        CarbonInterface $orderDate,
        User $actor,
    ): PurchaseOrder {
        Gate::forUser($actor)->authorize('create', PurchaseOrder::class);

        return DB::transaction(function () use ($actor, $lineSelections, $orderDate, $requisition, $vendor): PurchaseOrder {
            $requisition = PurchaseRequisition::query()->whereKey($requisition)->lockForUpdate()->firstOrFail();
            if (! in_array($requisition->status, [
                PurchaseRequisitionStatus::Approved,
                PurchaseRequisitionStatus::PartiallyOrdered,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Purchase orders may only originate from an approved requisition with remaining quantity.']);
            }

            if ((int) $vendor->company_id !== (int) $requisition->company_id
                || ! $vendor->hasRole(PartyRole::Vendor)) {
                throw ValidationException::withMessages(['vendor_id' => 'Select a vendor belonging to the requisition company.']);
            }

            if ($lineSelections === []) {
                throw ValidationException::withMessages(['lines' => 'Select at least one requisition line.']);
            }

            $order = PurchaseOrder::query()->create([
                'company_id' => $requisition->company_id,
                'purchase_requisition_id' => $requisition->getKey(),
                'vendor_id' => $vendor->getKey(),
                'project_id' => $requisition->project_id,
                'project_site_id' => $requisition->project_site_id,
                'order_date' => $orderDate,
                'currency_code' => $requisition->currency_code,
                'payment_terms_days' => $vendor->payment_terms_days,
                'prepared_by_id' => $actor->getKey(),
            ]);

            foreach (array_values($lineSelections) as $index => $selection) {
                $sourceLine = $requisition->lines()->whereKey($selection['purchase_requisition_line_id'])
                    ->lockForUpdate()->first();
                $quantity = number_format((float) $selection['quantity'], 4, '.', '');
                $availableQuantity = $sourceLine === null
                    ? '0.0000'
                    : bcsub((string) $sourceLine->quantity, (string) $sourceLine->ordered_quantity, 4);

                if ($sourceLine === null || bccomp($quantity, '0', 4) !== 1 || bccomp($quantity, $availableQuantity, 4) === 1) {
                    throw ValidationException::withMessages(['lines' => 'Every selected quantity must be positive and within the requisition balance.']);
                }

                $order->lines()->create([
                    'company_id' => $order->company_id,
                    'purchase_requisition_line_id' => $sourceLine->getKey(),
                    'line_number' => $index + 1,
                    'item_id' => $sourceLine->item_id,
                    'unit_of_measure_id' => $sourceLine->unit_of_measure_id,
                    'tax_code_id' => $selection['tax_code_id'] ?? null,
                    'quantity' => $quantity,
                    'unit_rate' => $selection['unit_rate'] ?? $sourceLine->estimated_rate,
                    'specification' => $sourceLine->specification,
                ]);
            }

            activity('purchase_orders')->causedBy($actor)->performedOn($order)->event('created_from_requisition')
                ->withProperties([
                    'company_id' => $order->company_id,
                    'purchase_requisition_id' => $requisition->getKey(),
                    'line_count' => count($lineSelections),
                ])->log('created purchase order from requisition');

            return $order->load('lines');
        });
    }
}
