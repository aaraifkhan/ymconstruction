<?php

namespace App\Actions\Inventory;

use App\Enums\AccountingMappingKey;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Models\AccountingMapping;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HandoverGoodsReceiptToAccountsAction
{
    public function __construct(
        private ApplyInventoryMovementAction $applyMovement,
        private CreateInventoryJournalAction $createJournal,
    ) {}

    public function handle(GoodsReceipt $receipt, User $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('handover', $receipt);

        return DB::transaction(function () use ($actor, $receipt): GoodsReceipt {
            $receipt = GoodsReceipt::query()->whereKey($receipt)->lockForUpdate()->firstOrFail();
            if ($receipt->status === GoodsReceiptStatus::HandedOver) {
                return $receipt;
            }
            if ($receipt->status !== GoodsReceiptStatus::Inspected) {
                throw ValidationException::withMessages(['status' => 'Only an inspected Goods Receipt may be handed to Accounts.']);
            }
            if (in_array((int) $actor->getKey(), [(int) $receipt->received_by_id, (int) $receipt->inspected_by_id], true)) {
                throw ValidationException::withMessages(['handed_over_by_id' => 'Receiving, inspection, and Accounts handover require separate actors.']);
            }

            $lines = $receipt->lines()->orderBy('item_id')->lockForUpdate()->get();
            $acceptedTotal = '0.0000';
            foreach ($lines as $line) {
                if (bccomp((string) $line->accepted_quantity, '0', 4) !== 1) {
                    continue;
                }

                $movement = $this->applyMovement->handle(
                    companyId: $receipt->company_id,
                    siteId: $receipt->project_site_id,
                    itemId: $line->item_id,
                    direction: InventoryMovementDirection::In,
                    quantity: (string) $line->accepted_quantity,
                    unitCost: (string) $line->unit_cost_snapshot,
                    movementType: InventoryMovementType::GoodsReceipt,
                    source: $receipt,
                    actor: $actor,
                    projectId: $receipt->project_id,
                );
                $acceptedTotal = bcadd($acceptedTotal, (string) $movement->movement_value, 4);
            }

            $journal = null;
            if (bccomp($acceptedTotal, '0', 4) === 1) {
                $inventoryAccountId = $this->mappedAccountId($receipt->company_id, AccountingMappingKey::SiteInventory);
                $grniAccountId = $this->mappedAccountId($receipt->company_id, AccountingMappingKey::Grni);
                $journal = $this->createJournal->handle(
                    source: $receipt,
                    transactionDate: $receipt->inspected_at,
                    reference: $receipt->goods_receipt_number,
                    description: "Accepted inventory for {$receipt->goods_receipt_number}",
                    preparedById: $receipt->received_by_id,
                    approvedById: $receipt->inspected_by_id,
                    postingActor: $actor,
                    lines: [
                        [
                            'account_id' => $inventoryAccountId,
                            'debit' => $acceptedTotal,
                            'credit' => '0.0000',
                            'description' => 'Accepted site inventory',
                            'project_id' => $receipt->project_id,
                            'project_site_id' => $receipt->project_site_id,
                        ],
                        [
                            'account_id' => $grniAccountId,
                            'debit' => '0.0000',
                            'credit' => $acceptedTotal,
                            'description' => 'Goods received not invoiced',
                            'party_id' => $receipt->vendor_id,
                            'project_id' => $receipt->project_id,
                            'project_site_id' => $receipt->project_site_id,
                        ],
                    ],
                );
            }

            $receipt->update([
                'status' => GoodsReceiptStatus::HandedOver,
                'handed_over_by_id' => $actor->getKey(),
                'handed_over_at' => now(),
                'inventory_journal_entry_id' => $journal?->getKey(),
                'accepted_value' => $acceptedTotal,
            ]);

            activity('goods_receipts')->causedBy($actor)->performedOn($receipt)->event('handed_over')
                ->withProperties([
                    'company_id' => $receipt->company_id,
                    'accepted_value' => $acceptedTotal,
                    'journal_entry_id' => $journal?->getKey(),
                ])->log('handed inspected Goods Receipt to Accounts');

            return $receipt->refresh();
        }, attempts: 3);
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
}
