<?php

namespace App\Reports;

use App\Enums\AccountType;
use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProfitAndLossReport
{
    public function __construct(private TrialBalanceReport $trialBalance) {}

    /** @return array{revenue:Collection, expenses:Collection, revenue_total:string, expense_total:string, profit_or_loss:string} */
    public function forCompany(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $this->trialBalance->forCompany($company, $to, $from);
        $revenue = $rows->where('account_type', AccountType::Revenue)->values();
        $expenses = $rows->where('account_type', AccountType::Expense)->values();
        $revenueTotal = $this->sum($revenue);
        $expenseTotal = $this->sum($expenses);

        return [
            'revenue' => $revenue, 'expenses' => $expenses,
            'revenue_total' => $revenueTotal, 'expense_total' => $expenseTotal,
            'profit_or_loss' => bcsub($revenueTotal, $expenseTotal, 4),
        ];
    }

    private function sum(Collection $rows): string
    {
        return $rows->reduce(fn (string $total, array $row): string => bcadd($total, $row['natural_balance'], 4), '0.0000');
    }
}
