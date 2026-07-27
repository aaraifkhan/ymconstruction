<?php

namespace App\Actions\Projects;

use App\Enums\ProjectBudgetStatus;
use App\Models\ProjectBudget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveProjectBudgetAction
{
    public function handle(ProjectBudget $budget, User $actor): ProjectBudget
    {
        Gate::forUser($actor)->authorize('approve', $budget);

        return DB::transaction(function () use ($actor, $budget): ProjectBudget {
            $lockedBudget = ProjectBudget::query()
                ->whereKey($budget)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBudget->status !== ProjectBudgetStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft budget can be approved.',
                ]);
            }

            if ($lockedBudget->prepared_by_id === $actor->getKey()) {
                throw ValidationException::withMessages([
                    'approved_by_id' => 'The budget preparer cannot approve the same budget.',
                ]);
            }

            $lines = $lockedBudget->lines()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Add at least one budget line before approval.',
                ]);
            }

            $totalAmount = $lines->reduce(
                fn (string $total, $line): string => bcadd($total, (string) $line->amount, 4),
                '0.0000',
            );

            $supersededBudgetIds = ProjectBudget::query()
                ->where('project_id', $lockedBudget->project_id)
                ->where('status', ProjectBudgetStatus::Approved)
                ->whereKeyNot($lockedBudget)
                ->lockForUpdate()
                ->pluck('id');

            if ($supersededBudgetIds->isNotEmpty()) {
                ProjectBudget::query()
                    ->whereKey($supersededBudgetIds)
                    ->update([
                        'status' => ProjectBudgetStatus::Superseded,
                        'updated_at' => now(),
                    ]);
            }

            $lockedBudget->update([
                'status' => ProjectBudgetStatus::Approved,
                'total_amount' => $totalAmount,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);

            activity('project_budgets')
                ->causedBy($actor)
                ->performedOn($lockedBudget)
                ->event('approved')
                ->withProperties([
                    'company_id' => $lockedBudget->company_id,
                    'project_id' => $lockedBudget->project_id,
                    'version' => $lockedBudget->version,
                    'line_count' => $lines->count(),
                    'total_amount' => $totalAmount,
                    'superseded_budget_ids' => $supersededBudgetIds->all(),
                ])
                ->log("approved project budget version {$lockedBudget->version}");

            return $lockedBudget->refresh();
        });
    }
}
