<?php

namespace App\Reports;

use App\Enums\AccountType;
use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BalanceSheetReport
{
    public function __construct(private TrialBalanceReport $trialBalance) {}

    /** @return array{assets:Collection, liabilities:Collection, equity:Collection, asset_total:string, liability_total:string, equity_total:string, current_result:string, balances:bool} */
    public function forCompany(Company $company, CarbonInterface $asOf): array
    {
        $rows = $this->trialBalance->forCompany($company, $asOf);
        $assets = $rows->where('account_type', AccountType::Asset)->values();
        $liabilities = $rows->where('account_type', AccountType::Liability)->values();
        $equity = $rows->where('account_type', AccountType::Equity)->values();
        $result = $rows->whereIn('account_type', [AccountType::Revenue, AccountType::Expense])
            ->reduce(fn (string $total, array $row): string => bcadd($total, $row['account_type'] === AccountType::Revenue ? $row['natural_balance'] : bcmul($row['natural_balance'], '-1', 4), 4), '0.0000');
        $assetTotal = $this->sum($assets);
        $liabilityTotal = $this->sum($liabilities);
        $equityTotal = bcadd($this->sum($equity), $result, 4);

        return [
            'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity,
            'asset_total' => $assetTotal, 'liability_total' => $liabilityTotal,
            'equity_total' => $equityTotal, 'current_result' => $result,
            'balances' => bccomp($assetTotal, bcadd($liabilityTotal, $equityTotal, 4), 4) === 0,
        ];
    }

    private function sum(Collection $rows): string
    {
        return $rows->reduce(fn (string $total, array $row): string => bcadd($total, $row['natural_balance'], 4), '0.0000');
    }
}
