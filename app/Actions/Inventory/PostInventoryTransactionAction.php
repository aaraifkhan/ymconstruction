<?php

namespace App\Actions\Inventory;

use App\Actions\Procurement\ReserveProcurementNumberAction;
use App\Enums\AccountingMappingKey;
use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryTransactionStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\ProcurementDocumentType;
use App\Models\AccountingMapping;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostInventoryTransactionAction
{
    public function __construct(
        private ApplyInventoryMovementAction $applyMovement,
        private CreateInventoryJournalAction $createJournal,
        private ReserveProcurementNumberAction $reserveNumber,
        private RefreshPurchaseOrderReceiptStatusAction $refreshOrderStatus,
    ) {}

    public function handle(InventoryTransaction $transaction, User $actor): InventoryTransaction
    {
        Gate::forUser($actor)->authorize('post', $transaction);

        return DB::transaction(function () use ($actor, $transaction): InventoryTransaction {
            $transaction = InventoryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status === InventoryTransactionStatus::Posted) {
                return $transaction;
            }
            if ((int) $transaction->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['posted_by_id' => 'The preparer cannot post the same inventory transaction.']);
            }

            if ($transaction->transaction_number === null) {
                $transaction->update([
                    'transaction_number' => $this->reserveNumber->handle(
                        $transaction->company,
                        ProcurementDocumentType::InventoryTransaction,
                        $transaction->transaction_date->year,
                    ),
                ]);
            }

            $lines = $transaction->lines()->orderBy('item_id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'An inventory transaction requires at least one line.']);
            }

            $journalLines = [];
            $totalValue = '0.0000';
            foreach ($lines as $line) {
                [$lineValue, $unitCost] = $this->applyLine($transaction, $line, $actor);
                $line->update([
                    'unit_cost_snapshot' => $unitCost,
                    'line_value' => $lineValue,
                ]);
                $totalValue = bcadd($totalValue, $lineValue, 4);
                $journalLines = [
                    ...$journalLines,
                    ...$this->journalLinesFor($transaction, $line, $lineValue),
                ];
            }

            $journal = null;
            if ($journalLines !== []) {
                $journal = $this->createJournal->handle(
                    source: $transaction,
                    transactionDate: $transaction->transaction_date,
                    reference: $transaction->transaction_number ?? 'Draft inventory transaction',
                    description: $transaction->type->getLabel().' — '.$transaction->reason,
                    preparedById: $transaction->prepared_by_id,
                    approvedById: $actor->getKey(),
                    postingActor: $actor,
                    lines: $journalLines,
                );
            }

            $transaction->update([
                'status' => InventoryTransactionStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'journal_entry_id' => $journal?->getKey(),
                'total_value' => $totalValue,
            ]);

            activity('inventory_transactions')->causedBy($actor)->performedOn($transaction)->event('posted')
                ->withProperties([
                    'company_id' => $transaction->company_id,
                    'type' => $transaction->type->value,
                    'line_count' => $lines->count(),
                    'total_value' => $totalValue,
                    'journal_entry_id' => $journal?->getKey(),
                ])->log('posted inventory transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function applyLine(
        InventoryTransaction $transaction,
        InventoryTransactionLine $line,
        User $actor,
    ): array {
        if ($transaction->type === InventoryTransactionType::Transfer) {
            $out = $this->applyMovement->handle(
                $transaction->company_id,
                $transaction->source_site_id,
                $line->item_id,
                InventoryMovementDirection::Out,
                (string) $line->quantity,
                null,
                InventoryMovementType::TransferOut,
                $transaction,
                $actor,
                $transaction->destination_site_id,
            );
            $this->applyMovement->handle(
                $transaction->company_id,
                $transaction->destination_site_id,
                $line->item_id,
                InventoryMovementDirection::In,
                (string) $line->quantity,
                (string) $out->unit_cost,
                InventoryMovementType::TransferIn,
                $transaction,
                $actor,
                $transaction->source_site_id,
            );

            return [(string) $out->movement_value, (string) $out->unit_cost];
        }

        if ($transaction->type->isOutbound()) {
            $receiptLine = null;
            if ($transaction->type === InventoryTransactionType::VendorReturn) {
                $receiptLine = GoodsReceiptLine::query()
                    ->whereKey($line->goods_receipt_line_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (bccomp((string) $line->quantity, $receiptLine->availableAcceptedToReturn(), 4) === 1) {
                    throw ValidationException::withMessages(['quantity' => 'Vendor return exceeds accepted material available to return.']);
                }
            }

            $movement = $this->applyMovement->handle(
                $transaction->company_id,
                $transaction->source_site_id,
                $line->item_id,
                InventoryMovementDirection::Out,
                (string) $line->quantity,
                null,
                match ($transaction->type) {
                    InventoryTransactionType::ProjectIssue => InventoryMovementType::ProjectIssue,
                    InventoryTransactionType::VendorReturn => InventoryMovementType::VendorReturn,
                    InventoryTransactionType::AdjustmentDecrease => InventoryMovementType::AdjustmentDecrease,
                    default => throw new \LogicException('Unsupported outbound inventory type.'),
                },
                $transaction,
                $actor,
                projectId: $transaction->project_id,
            );

            if ($receiptLine !== null) {
                $receiptLine->update([
                    'accepted_returned_quantity' => bcadd(
                        (string) $receiptLine->accepted_returned_quantity,
                        (string) $line->quantity,
                        4,
                    ),
                ]);
                $orderLine = $receiptLine->purchaseOrderLine()->lockForUpdate()->firstOrFail();
                $orderLine->update([
                    'received_quantity' => bcsub(
                        (string) $orderLine->received_quantity,
                        (string) $line->quantity,
                        4,
                    ),
                ]);
                $this->refreshOrderStatus->handle($orderLine->purchase_order_id);
            }

            return [(string) $movement->movement_value, (string) $movement->unit_cost];
        }

        $unitCost = $this->inboundUnitCost($transaction, $line);
        $movement = $this->applyMovement->handle(
            $transaction->company_id,
            $transaction->destination_site_id,
            $line->item_id,
            InventoryMovementDirection::In,
            (string) $line->quantity,
            $unitCost,
            $transaction->type === InventoryTransactionType::ProjectReturn
                ? InventoryMovementType::ProjectReturn
                : InventoryMovementType::AdjustmentIncrease,
            $transaction,
            $actor,
            projectId: $transaction->project_id,
        );

        return [(string) $movement->movement_value, (string) $movement->unit_cost];
    }

    private function inboundUnitCost(
        InventoryTransaction $transaction,
        InventoryTransactionLine $line,
    ): string {
        $currentAverage = InventoryBalance::query()
            ->where('company_id', $transaction->company_id)
            ->where('project_site_id', $transaction->destination_site_id)
            ->where('item_id', $line->item_id)
            ->lockForUpdate()
            ->value('average_unit_cost');

        if ($transaction->type === InventoryTransactionType::ProjectReturn
            && $currentAverage !== null
            && bccomp((string) $currentAverage, '0', 4) === 1) {
            return (string) $currentAverage;
        }

        if (bccomp((string) $line->unit_cost_snapshot, '0', 4) !== 1) {
            throw ValidationException::withMessages([
                'unit_cost_snapshot' => 'Inbound adjustment or first Project return requires a positive unit cost.',
            ]);
        }

        return (string) $line->unit_cost_snapshot;
    }

    /**
     * @return array<int, array{account_id:int, debit:string, credit:string, description:string, party_id?:int|null, project_id?:int|null, project_site_id?:int|null}>
     */
    private function journalLinesFor(
        InventoryTransaction $transaction,
        InventoryTransactionLine $line,
        string $lineValue,
    ): array {
        if ($transaction->type === InventoryTransactionType::Transfer) {
            return [];
        }

        $inventoryAccountId = $this->mappedAccountId($transaction->company_id, AccountingMappingKey::SiteInventory);
        $inventoryLine = [
            'account_id' => $inventoryAccountId,
            'debit' => $transaction->type->isInbound() ? $lineValue : '0.0000',
            'credit' => $transaction->type->isOutbound() ? $lineValue : '0.0000',
            'description' => $line->item_name_snapshot,
            'project_id' => $transaction->project_id,
            'project_site_id' => $transaction->source_site_id ?? $transaction->destination_site_id,
        ];

        $offsetAccountId = $transaction->type === InventoryTransactionType::VendorReturn
            ? $this->mappedAccountId($transaction->company_id, AccountingMappingKey::Grni)
            : (int) $line->offset_account_id;
        $offsetLine = [
            'account_id' => $offsetAccountId,
            'debit' => $transaction->type->isOutbound() ? $lineValue : '0.0000',
            'credit' => $transaction->type->isInbound() ? $lineValue : '0.0000',
            'description' => $transaction->type->getLabel().' — '.$line->item_name_snapshot,
            'project_id' => $transaction->project_id,
            'project_site_id' => $transaction->source_site_id ?? $transaction->destination_site_id,
        ];

        if ($transaction->type === InventoryTransactionType::VendorReturn) {
            $offsetLine['party_id'] = $transaction->goodsReceipt->vendor_id;
        }

        return [$inventoryLine, $offsetLine];
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
