<?php

namespace App\Actions\Inventory;

use App\Enums\GoodsReceiptStatus;
use App\Enums\InspectionResult;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InspectGoodsReceiptAction
{
    /**
     * @param  array<int, array{accepted_quantity:string, rejected_quantity:string, inspection_notes?:string|null, rejection_reason?:string|null}>  $decisions
     */
    public function handle(GoodsReceipt $receipt, User $actor, array $decisions, ?string $inspectionNotes = null): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('inspect', $receipt);

        return DB::transaction(function () use ($actor, $decisions, $inspectionNotes, $receipt): GoodsReceipt {
            $receipt = GoodsReceipt::query()->whereKey($receipt)->lockForUpdate()->firstOrFail();
            if ($receipt->status !== GoodsReceiptStatus::Received) {
                throw ValidationException::withMessages(['status' => 'Only a received delivery may be inspected.']);
            }
            if ((int) $receipt->received_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['inspected_by_id' => 'The receiver cannot inspect the same Goods Receipt.']);
            }

            $lines = $receipt->lines()->orderBy('id')->lockForUpdate()->get();
            if ($lines->count() !== count($decisions)) {
                throw ValidationException::withMessages(['lines' => 'Every received line requires an inspection decision.']);
            }

            $acceptedTotal = '0.0000';
            foreach ($lines as $line) {
                $decision = $decisions[$line->getKey()] ?? null;
                if ($decision === null) {
                    throw ValidationException::withMessages(['lines' => "Line {$line->line_number} has no inspection decision."]);
                }

                $accepted = (string) $decision['accepted_quantity'];
                $rejected = (string) $decision['rejected_quantity'];
                $result = match (true) {
                    bccomp($accepted, '0', 4) === 0 => InspectionResult::Rejected,
                    bccomp($rejected, '0', 4) === 0 => InspectionResult::Accepted,
                    default => InspectionResult::PartiallyAccepted,
                };
                $acceptedValue = bcmul($accepted, (string) $line->unit_cost_snapshot, 4);

                $line->update([
                    'accepted_quantity' => $accepted,
                    'rejected_quantity' => $rejected,
                    'accepted_value' => $acceptedValue,
                    'inspection_result' => $result,
                    'inspection_notes' => $decision['inspection_notes'] ?? null,
                    'rejection_reason' => $decision['rejection_reason'] ?? null,
                ]);
                $acceptedTotal = bcadd($acceptedTotal, $acceptedValue, 4);
            }

            $receipt->update([
                'status' => GoodsReceiptStatus::Inspected,
                'inspected_by_id' => $actor->getKey(),
                'inspected_at' => now(),
                'inspection_notes' => $inspectionNotes,
                'accepted_value' => $acceptedTotal,
            ]);

            activity('goods_receipts')->causedBy($actor)->performedOn($receipt)->event('inspected')
                ->withProperties([
                    'company_id' => $receipt->company_id,
                    'accepted_value' => $acceptedTotal,
                    'line_count' => $lines->count(),
                ])->log('inspected received materials');

            return $receipt->refresh();
        });
    }
}
