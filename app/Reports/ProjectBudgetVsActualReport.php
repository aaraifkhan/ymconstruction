<?php

namespace App\Reports;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\ProjectBudgetStatus;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Project;
use App\Models\ProjectBudget;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ProjectBudgetVsActualReport
{
    /** @return array{budget:string, actual:string, variance:string, utilization_percentage:?string, budget_version:?int} */
    public function forProject(Company $company, Project $project, CarbonInterface $asOf): array
    {
        if ((int) $project->company_id !== (int) $company->getKey()) {
            throw ValidationException::withMessages(['project' => 'Choose a Project belonging to the current company.']);
        }
        $budget = ProjectBudget::query()
            ->whereBelongsTo($company)
            ->where('project_id', $project->getKey())
            ->where('status', ProjectBudgetStatus::Approved)
            ->whereDate('approved_at', '<=', $asOf)
            ->latest('version')
            ->first();
        $budgetAmount = bcadd('0.0000', (string) ($budget?->lines()->sum('amount') ?? 0), 4);
        $lines = JournalLine::query()
            ->whereBelongsTo($company)
            ->where('project_id', $project->getKey())
            ->whereHas('account', fn ($query) => $query->where('account_type', AccountType::Expense))
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
                ->whereDate('transaction_date', '<=', $asOf))
            ->get(['debit', 'credit']);
        $actual = $lines->reduce(
            fn (string $total, JournalLine $line): string => bcadd($total, bcsub((string) $line->debit, (string) $line->credit, 4), 4),
            '0.0000',
        );

        return [
            'budget' => $budgetAmount,
            'actual' => $actual,
            'variance' => bcsub($budgetAmount, $actual, 4),
            'utilization_percentage' => bccomp($budgetAmount, '0', 4) === 1
                ? bcdiv(bcmul($actual, '100.0000', 4), $budgetAmount, 4)
                : null,
            'budget_version' => $budget?->version,
        ];
    }
}
