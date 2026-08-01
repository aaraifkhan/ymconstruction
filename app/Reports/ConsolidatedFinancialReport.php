<?php

namespace App\Reports;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ConsolidatedFinancialReport
{
    public function __construct(
        private TrialBalanceReport $trialBalance,
        private IntercompanyReconciliationReport $reconciliation,
    ) {}

    /** @return array<string, mixed> */
    public function forGroup(User $actor, Company $root, CarbonInterface $to, ?CarbonInterface $from = null): array
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('View:ConsolidatedReports'))) {
            throw ValidationException::withMessages(['authorization' => 'You are not authorized to view consolidated reports.']);
        }
        $companies = $this->groupCompanies($root);
        if ($companies->contains(fn (Company $company): bool => ! $actor->canAccessTenant($company))) {
            throw ValidationException::withMessages(['authorization' => 'Consolidation requires access to every company in the selected group.']);
        }

        $rows = $companies->flatMap(fn (Company $company) => $this->trialBalance->forCompany($company, $to, $from)
            ->map(fn (array $row): array => [...$row, 'company_id' => $company->getKey(), 'company_name' => $company->name]));
        $eliminations = $this->internalEliminations($companies, $to, $from);
        $consolidated = $rows->groupBy(fn (array $row): string => $row['account_template_id']
            ? "template:{$row['account_template_id']}"
            : "{$row['account_type']->value}:{$row['reporting_group']}:{$row['code']}")
            ->map(function (Collection $groupRows, string $mappingKey) use ($eliminations): array {
                $first = $groupRows->first();
                $debit = $groupRows->reduce(fn (string $total, array $row): string => bcadd($total, $row['debit_total'], 4), '0.0000');
                $credit = $groupRows->reduce(fn (string $total, array $row): string => bcadd($total, $row['credit_total'], 4), '0.0000');
                $elimination = $eliminations->get($mappingKey, ['debit' => '0.0000', 'credit' => '0.0000']);
                $debit = bcsub($debit, $elimination['debit'], 4);
                $credit = bcsub($credit, $elimination['credit'], 4);
                $net = bcsub($debit, $credit, 4);

                return [
                    'mapping_key' => $mappingKey,
                    'code' => $first['code'],
                    'name' => $first['name'],
                    'reporting_group' => $first['reporting_group'],
                    'account_type' => $first['account_type'],
                    'normal_balance' => $first['normal_balance'],
                    'debit_total' => $debit,
                    'credit_total' => $credit,
                    'debit_balance' => bccomp($net, '0', 4) === 1 ? $net : '0.0000',
                    'credit_balance' => bccomp($net, '0', 4) === -1 ? bcmul($net, '-1', 4) : '0.0000',
                    'natural_balance' => $first['normal_balance']->value === 'debit' ? $net : bcmul($net, '-1', 4),
                ];
            })->sortBy('code')->values();
        $totals = $this->trialBalance->totals($consolidated);
        $reconciliation = $this->reconciliation->forCompanies($companies, $to);

        return [
            'root_company' => $root,
            'companies' => $companies,
            'company_rows' => $rows,
            'trial_balance' => $consolidated,
            'trial_balance_totals' => $totals,
            'balance_sheet' => $this->balanceSheet($consolidated),
            'profit_and_loss' => $this->profitAndLoss($consolidated),
            'intercompany_reconciliation' => $reconciliation,
            'reconciles' => bccomp($totals['debit'], $totals['credit'], 4) === 0
                && $reconciliation->every('reconciles'),
        ];
    }

    /** @return Collection<int, Company> */
    private function groupCompanies(Company $root): Collection
    {
        return Company::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<string, array{debit:string,credit:string}> */
    private function internalEliminations(Collection $companies, CarbonInterface $to, ?CarbonInterface $from): Collection
    {
        $companyIds = $companies->modelKeys();

        return JournalLine::query()->with('account')->whereIn('company_id', $companyIds)
            ->whereIn('related_company_id', $companyIds)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
                ->whereDate('transaction_date', '<=', $to)
                ->when($from, fn ($query) => $query->whereDate('transaction_date', '>=', $from)))
            ->get()->groupBy(fn (JournalLine $line): string => $line->account->account_template_id
                ? "template:{$line->account->account_template_id}"
                : "{$line->account->account_type->value}:{$line->account->reporting_group}:{$line->account->code}")
            ->map(fn (Collection $lines): array => [
                'debit' => $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->debit, 4), '0.0000'),
                'credit' => $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->credit, 4), '0.0000'),
            ]);
    }

    /** @return array<string, mixed> */
    private function balanceSheet(Collection $rows): array
    {
        $assets = $rows->where('account_type', AccountType::Asset)->values();
        $liabilities = $rows->where('account_type', AccountType::Liability)->values();
        $equity = $rows->where('account_type', AccountType::Equity)->values();
        $result = $this->profitAndLoss($rows)['profit_or_loss'];
        $assetTotal = $this->naturalSum($assets);
        $liabilityTotal = $this->naturalSum($liabilities);
        $equityTotal = bcadd($this->naturalSum($equity), $result, 4);

        return [
            'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity,
            'asset_total' => $assetTotal, 'liability_total' => $liabilityTotal,
            'equity_total' => $equityTotal, 'current_result' => $result,
            'balances' => bccomp($assetTotal, bcadd($liabilityTotal, $equityTotal, 4), 4) === 0,
        ];
    }

    /** @return array<string, mixed> */
    private function profitAndLoss(Collection $rows): array
    {
        $revenue = $rows->where('account_type', AccountType::Revenue)->values();
        $expenses = $rows->where('account_type', AccountType::Expense)->values();
        $revenueTotal = $this->naturalSum($revenue);
        $expenseTotal = $this->naturalSum($expenses);

        return [
            'revenue' => $revenue, 'expenses' => $expenses,
            'revenue_total' => $revenueTotal, 'expense_total' => $expenseTotal,
            'profit_or_loss' => bcsub($revenueTotal, $expenseTotal, 4),
        ];
    }

    private function naturalSum(Collection $rows): string
    {
        return $rows->reduce(fn (string $total, array $row): string => bcadd($total, $row['natural_balance'], 4), '0.0000');
    }
}
