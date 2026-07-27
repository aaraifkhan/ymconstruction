<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\ProjectBudgetLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use Illuminate\Validation\ValidationException;

class ValidatePurchaseRequisitionAction
{
    /**
     * @return array{estimated_total: string, budget_check_status: string, budget_check_snapshot: array<int, array<string, mixed>>}
     */
    public function handle(PurchaseRequisition $requisition): array
    {
        $lines = $requisition->lines()->orderBy('line_number')->lockForUpdate()->get();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Add at least one requisition line before submission.']);
        }

        $estimatedTotal = '0.0000';
        $budgetSnapshot = [];
        $linkedLineCount = 0;
        $currentRequestedByBudgetLine = [];

        foreach ($lines as $line) {
            $estimatedTotal = bcadd($estimatedTotal, (string) $line->estimated_amount, 4);

            if ($line->project_budget_line_id === null) {
                $budgetSnapshot[] = [
                    'requisition_line_id' => $line->getKey(),
                    'budget_line_id' => null,
                    'status' => 'not_linked',
                    'requested_amount' => $line->estimated_amount,
                ];

                continue;
            }

            $linkedLineCount++;
            $budgetLine = ProjectBudgetLine::query()->whereKey($line->project_budget_line_id)->lockForUpdate()->firstOrFail();
            $committedAmount = PurchaseRequisitionLine::query()
                ->where('project_budget_line_id', $budgetLine->getKey())
                ->whereKeyNot($line)
                ->whereHas('requisition', fn ($query) => $query->whereIn('status', [
                    PurchaseRequisitionStatus::Submitted,
                    PurchaseRequisitionStatus::Approved,
                    PurchaseRequisitionStatus::PartiallyOrdered,
                    PurchaseRequisitionStatus::Ordered,
                ]))
                ->lockForUpdate()
                ->get(['estimated_amount'])
                ->reduce(
                    fn (string $total, PurchaseRequisitionLine $committedLine): string => bcadd(
                        $total,
                        (string) $committedLine->estimated_amount,
                        4,
                    ),
                    '0.0000',
                );

            $availableAmount = bcsub((string) $budgetLine->amount, (string) $committedAmount, 4);
            $currentRequested = bcadd(
                $currentRequestedByBudgetLine[$budgetLine->getKey()] ?? '0.0000',
                (string) $line->estimated_amount,
                4,
            );
            $currentRequestedByBudgetLine[$budgetLine->getKey()] = $currentRequested;

            if (bccomp($currentRequested, $availableAmount, 4) === 1) {
                throw ValidationException::withMessages([
                    'budget' => "Requisition line {$line->line_number} exceeds its remaining approved budget.",
                ]);
            }

            $budgetSnapshot[] = [
                'requisition_line_id' => $line->getKey(),
                'budget_line_id' => $budgetLine->getKey(),
                'budget_amount' => $budgetLine->amount,
                'previous_commitments' => $committedAmount,
                'available_before_request' => $availableAmount,
                'requested_amount' => $line->estimated_amount,
                'current_requisition_total_for_budget_line' => $currentRequested,
                'status' => 'passed',
            ];
        }

        $budgetStatus = match (true) {
            $linkedLineCount === 0 => 'not_linked',
            $linkedLineCount === $lines->count() => 'passed',
            default => 'partially_linked',
        };

        return [
            'estimated_total' => $estimatedTotal,
            'budget_check_status' => $budgetStatus,
            'budget_check_snapshot' => $budgetSnapshot,
        ];
    }
}
