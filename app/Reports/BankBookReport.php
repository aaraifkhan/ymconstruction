<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BankBookReport
{
    /** @return array{opening_balance:string, debit_total:string, credit_total:string, closing_balance:string, lines:Collection<int, JournalLine>} */
    public function forBank(
        Company $company,
        CompanyBankAccount $bankAccount,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        if ((int) $bankAccount->company_id !== (int) $company->getKey()) {
            throw ValidationException::withMessages(['company_bank_account_id' => 'Bank account must belong to the report company.']);
        }

        $base = JournalLine::query()
            ->whereBelongsTo($company)
            ->where('company_bank_account_id', $bankAccount->getKey())
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value]));
        $openingLines = (clone $base)->whereHas('journalEntry', fn ($query) => $query->whereDate('transaction_date', '<', $from));
        $periodLines = (clone $base)->whereHas('journalEntry', fn ($query) => $query
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to));
        $opening = bcsub((string) (clone $openingLines)->sum('debit'), (string) (clone $openingLines)->sum('credit'), 4);
        $debits = (string) (clone $periodLines)->sum('debit');
        $credits = (string) (clone $periodLines)->sum('credit');

        return [
            'opening_balance' => $opening,
            'debit_total' => $debits,
            'credit_total' => $credits,
            'closing_balance' => bcadd($opening, bcsub($debits, $credits, 4), 4),
            'lines' => $periodLines->with(['journalEntry', 'account'])->get()
                ->sortBy(fn (JournalLine $line): string => $line->journalEntry->transaction_date->format('Y-m-d').'-'.str_pad((string) $line->line_number, 6, '0', STR_PAD_LEFT))
                ->values(),
        ];
    }
}
