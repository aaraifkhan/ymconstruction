<?php

namespace App\Reports;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProjectProfitabilityReport
{
    /** @return array{revenue:string, direct_cost:string, gross_profit:string, margin_percentage:?string, lines:Collection<int, JournalLine>} */
    public function forProject(Company $company, Project $project, CarbonInterface $from, CarbonInterface $to): array
    {
        if ((int) $project->company_id !== (int) $company->getKey() || $from->gt($to)) {
            throw ValidationException::withMessages(['project' => 'Choose a same-company Project and valid date range.']);
        }
        $lines = JournalLine::query()
            ->whereBelongsTo($company)
            ->where('project_id', $project->getKey())
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
                ->whereBetween('transaction_date', [$from, $to]))
            ->whereHas('account', fn ($query) => $query->whereIn('account_type', [AccountType::Revenue, AccountType::Expense]))
            ->with(['account:id,code,name,account_type', 'journalEntry:id,voucher_number,transaction_date,status'])
            ->get();
        $revenue = $lines->filter(fn (JournalLine $line): bool => $line->account->account_type === AccountType::Revenue)
            ->reduce(fn (string $total, JournalLine $line): string => bcadd($total, bcsub((string) $line->credit, (string) $line->debit, 4), 4), '0.0000');
        $directCost = $lines->filter(fn (JournalLine $line): bool => $line->account->account_type === AccountType::Expense)
            ->reduce(fn (string $total, JournalLine $line): string => bcadd($total, bcsub((string) $line->debit, (string) $line->credit, 4), 4), '0.0000');
        $profit = bcsub($revenue, $directCost, 4);

        return [
            'revenue' => $revenue,
            'direct_cost' => $directCost,
            'gross_profit' => $profit,
            'margin_percentage' => bccomp($revenue, '0', 4) === 1
                ? bcdiv(bcmul($profit, '100.0000', 4), $revenue, 4)
                : null,
            'lines' => $lines,
        ];
    }
}
