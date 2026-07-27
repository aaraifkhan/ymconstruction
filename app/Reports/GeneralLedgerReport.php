<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GeneralLedgerReport
{
    /** @return array{account:Account, opening_balance:string, debit_total:string, credit_total:string, closing_balance:string, lines:Collection} */
    public function forAccount(Company $company, Account $account, CarbonInterface $from, CarbonInterface $to): array
    {
        if ((int) $account->company_id !== (int) $company->getKey() || $from->gt($to)) {
            throw ValidationException::withMessages(['account' => 'Choose a company account and a valid date range.']);
        }

        $base = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $account->getKey())
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed]));

        $openingLines = (clone $base)->whereHas('journalEntry', fn ($query) => $query->whereDate('transaction_date', '<', $from))->get(['debit', 'credit']);
        $opening = $this->net($openingLines);
        $lines = (clone $base)
            ->whereHas('journalEntry', fn ($query) => $query->whereBetween('transaction_date', [$from, $to]))
            ->with(['journalEntry:id,voucher_number,transaction_date,description,status'])
            ->get();
        $debitTotal = $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->debit, 4), '0.0000');
        $creditTotal = $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->credit, 4), '0.0000');

        return [
            'account' => $account,
            'opening_balance' => $opening,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'closing_balance' => bcsub(bcadd($opening, $debitTotal, 4), $creditTotal, 4),
            'lines' => $lines,
        ];
    }

    private function net(Collection $lines): string
    {
        return $lines->reduce(
            fn (string $total, JournalLine $line): string => bcadd($total, bcsub((string) $line->debit, (string) $line->credit, 4), 4),
            '0.0000',
        );
    }
}
