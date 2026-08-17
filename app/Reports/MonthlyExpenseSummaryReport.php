<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;

class MonthlyExpenseSummaryReport
{
    /**
     * @return array{
     *     company: Company,
     *     from: string,
     *     to: string,
     *     head_office_expenses: array{total: string, items: array<string, array{label: string, code: string, count: int, total: string}>},
     *     bidding_expenses: array{total: string, items: array<string, array{label: string, code: string, count: int, total: string}>},
     *     director_funded_expenses: array{total: string, items: array<string, array{label: string, code: string, count: int, total: string}>},
     *     project_expenses: array{total: string, by_project: array<int, array{name: string, code: string, total: string, count: int}>},
     *     shared_entity_expenses: array{total: string, by_company: array<int, array{name: string, total: string, count: int}>},
     *     funding_sources: array<string, array{label: string, total: string}>,
     *     grand_total_expenditure: string
     * }
     */
    public function forCompany(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        // 1. All debit expense/cost lines in the period
        $lines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to))
            ->with(['journalEntry.lines.account', 'project', 'relatedCompany', 'account'])
            ->get();

        $hoTotal = '0.0000';
        $hoItems = [];

        $biddingTotal = '0.0000';
        $biddingItems = [];

        $directorTotal = '0.0000';
        $directorItems = [];

        $projectTotal = '0.0000';
        $projectItems = [];

        $sharedTotal = '0.0000';
        $sharedItems = [];

        $fundingSources = [
            'cash' => ['label' => 'Head Office Cash', 'total' => '0.0000'],
            'petty_cash' => ['label' => 'Petty Cash Float', 'total' => '0.0000'],
            'bank' => ['label' => 'Company Bank Accounts', 'total' => '0.0000'],
            'director' => ['label' => 'Director Funded (Due to Director)', 'total' => '0.0000'],
            'payable' => ['label' => 'Staff / Other Payables', 'total' => '0.0000'],
        ];

        $directorMapping = AccountingMapping::where('company_id', $company->getKey())
            ->where('system_key', AccountingMappingKey::DirectorLoan)
            ->where('is_active', true)
            ->first();
        $directorAccountId = $directorMapping?->account_id;

        foreach ($lines as $line) {
            $code = $line->account_code_snapshot ?: ($line->account?->code ?? '');
            $name = $line->account_name_snapshot ?: ($line->account?->name ?? '');
            $amount = (string) $line->debit;

            // Check if this is an expense line (5000-7999 or fixed asset 1200s)
            $isExpense = str_starts_with($code, '5') || str_starts_with($code, '6') || str_starts_with($code, '7');
            if (! $isExpense) {
                continue;
            }

            // Find contra line to determine funding source
            $contraLine = $line->journalEntry->lines->firstWhere('id', '!=', $line->id);
            $contraCode = $contraLine?->account_code_snapshot ?: ($contraLine?->account?->code ?? '');
            $contraAccId = $contraLine?->account_id;

            $isDirectorFunded = ($directorAccountId !== null && (int) $contraAccId === (int) $directorAccountId)
                || $contraCode === '2220';

            // Track funding source
            if ($isDirectorFunded) {
                $fundingSources['director']['total'] = bcadd($fundingSources['director']['total'], $amount, 4);
            } elseif ($contraCode === '1111') {
                $fundingSources['cash']['total'] = bcadd($fundingSources['cash']['total'], $amount, 4);
            } elseif ($contraCode === '1112') {
                $fundingSources['petty_cash']['total'] = bcadd($fundingSources['petty_cash']['total'], $amount, 4);
            } elseif (str_starts_with($contraCode, '1120')) {
                $fundingSources['bank']['total'] = bcadd($fundingSources['bank']['total'], $amount, 4);
            } else {
                $fundingSources['payable']['total'] = bcadd($fundingSources['payable']['total'], $amount, 4);
            }

            // Pillar 2: Bidding Expenses (5050 - 5058)
            if (str_starts_with($code, '505')) {
                $biddingTotal = bcadd($biddingTotal, $amount, 4);
                if (! isset($biddingItems[$code])) {
                    $biddingItems[$code] = ['label' => $name, 'code' => $code, 'count' => 0, 'total' => '0.0000'];
                }
                $biddingItems[$code]['count']++;
                $biddingItems[$code]['total'] = bcadd($biddingItems[$code]['total'], $amount, 4);

                continue;
            }

            // Pillar 4: Project Direct Expenses (has project_id or 7000 series)
            if ($line->project_id !== null || str_starts_with($code, '7')) {
                $projectTotal = bcadd($projectTotal, $amount, 4);
                $pId = $line->project_id ?? 0;
                $pName = $line->project?->name ?? 'General Project / Unassigned';
                $pCode = $line->project?->code ?? 'PROJ';

                if (! isset($projectItems[$pId])) {
                    $projectItems[$pId] = ['name' => $pName, 'code' => $pCode, 'count' => 0, 'total' => '0.0000'];
                }
                $projectItems[$pId]['count']++;
                $projectItems[$pId]['total'] = bcadd($projectItems[$pId]['total'], $amount, 4);

                continue;
            }

            // Pillar 5: Shared Entity Expense (related_company_id set)
            if ($line->related_company_id !== null) {
                $sharedTotal = bcadd($sharedTotal, $amount, 4);
                $rId = (int) $line->related_company_id;
                $rName = $line->relatedCompany?->name ?? 'Related Entity';

                if (! isset($sharedItems[$rId])) {
                    $sharedItems[$rId] = ['name' => $rName, 'count' => 0, 'total' => '0.0000'];
                }
                $sharedItems[$rId]['count']++;
                $sharedItems[$rId]['total'] = bcadd($sharedItems[$rId]['total'], $amount, 4);
            }

            // Pillar 3: Director Funded
            if ($isDirectorFunded) {
                $directorTotal = bcadd($directorTotal, $amount, 4);
                if (! isset($directorItems[$code])) {
                    $directorItems[$code] = ['label' => $name, 'code' => $code, 'count' => 0, 'total' => '0.0000'];
                }
                $directorItems[$code]['count']++;
                $directorItems[$code]['total'] = bcadd($directorItems[$code]['total'], $amount, 4);
            }

            // Pillar 1: Head Office / Operating Expenses (5000-6999 excluding 5050s)
            if (str_starts_with($code, '5') || str_starts_with($code, '6')) {
                $hoTotal = bcadd($hoTotal, $amount, 4);
                if (! isset($hoItems[$code])) {
                    $hoItems[$code] = ['label' => $name, 'code' => $code, 'count' => 0, 'total' => '0.0000'];
                }
                $hoItems[$code]['count']++;
                $hoItems[$code]['total'] = bcadd($hoItems[$code]['total'], $amount, 4);
            }
        }

        $grandTotal = bcadd($hoTotal, bcadd($biddingTotal, $projectTotal, 4), 4);

        return [
            'company' => $company,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'head_office_expenses' => [
                'total' => $hoTotal,
                'items' => $hoItems,
            ],
            'bidding_expenses' => [
                'total' => $biddingTotal,
                'items' => $biddingItems,
            ],
            'director_funded_expenses' => [
                'total' => $directorTotal,
                'items' => $directorItems,
            ],
            'project_expenses' => [
                'total' => $projectTotal,
                'by_project' => $projectItems,
            ],
            'shared_entity_expenses' => [
                'total' => $sharedTotal,
                'by_company' => $sharedItems,
            ],
            'funding_sources' => $fundingSources,
            'grand_total_expenditure' => $grandTotal,
        ];
    }
}
