<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TrialBalanceReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company, CarbonInterface $to, ?CarbonInterface $from = null): Collection
    {
        $lineQuery = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->whereHas('journalEntry', function ($query) use ($from, $to): void {
                $query->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
                    ->whereDate('transaction_date', '<=', $to)
                    ->when($from, fn ($query) => $query->whereDate('transaction_date', '>=', $from));
            });

        $totals = $lineQuery->selectRaw('account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->groupBy('account_id')->get()->keyBy('account_id');

        return Account::query()->where('company_id', $company->getKey())->whereKey($totals->keys())
            ->orderBy('code')->get()->map(function (Account $account) use ($totals): array {
                $total = $totals[$account->getKey()];
                $debit = number_format((float) $total->debit_total, 4, '.', '');
                $credit = number_format((float) $total->credit_total, 4, '.', '');
                $net = bcsub($debit, $credit, 4);

                return [
                    'account_id' => $account->getKey(),
                    'account_template_id' => $account->account_template_id,
                    'reporting_group' => $account->reporting_group,
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'normal_balance' => $account->normal_balance,
                    'debit_total' => $debit,
                    'credit_total' => $credit,
                    'debit_balance' => bccomp($net, '0', 4) === 1 ? $net : '0.0000',
                    'credit_balance' => bccomp($net, '0', 4) === -1 ? bcmul($net, '-1', 4) : '0.0000',
                    'natural_balance' => $account->normal_balance->value === 'debit' ? $net : bcmul($net, '-1', 4),
                ];
            })->values();
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function totals(Collection $rows): array
    {
        return [
            'debit' => $rows->reduce(fn (string $total, array $row): string => bcadd($total, $row['debit_balance'], 4), '0.0000'),
            'credit' => $rows->reduce(fn (string $total, array $row): string => bcadd($total, $row['credit_balance'], 4), '0.0000'),
        ];
    }
}
