<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Party;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class VendorLedgerReport
{
    /** @return array{opening_balance:string, debit_total:string, credit_total:string, closing_balance:string, lines:Collection<int, JournalLine>} */
    public function forVendor(
        Company $company,
        Party $vendor,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        if ((int) $vendor->company_id !== (int) $company->getKey() || $from->gt($to)) {
            throw ValidationException::withMessages(['vendor' => 'Choose a same-company vendor and valid date range.']);
        }
        $accountId = AccountingMapping::query()
            ->whereBelongsTo($company)
            ->where('system_key', AccountingMappingKey::AccountsPayable)
            ->where('is_active', true)
            ->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => 'Accounts Payable mapping is required.']);
        }

        $query = JournalLine::query()
            ->whereBelongsTo($company)
            ->where('account_id', $accountId)
            ->where('party_id', $vendor->getKey())
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed]));
        $opening = $this->creditNet((clone $query)
            ->whereHas('journalEntry', fn ($query) => $query->whereDate('transaction_date', '<', $from))
            ->get(['debit', 'credit']));
        $lines = (clone $query)
            ->whereHas('journalEntry', fn ($query) => $query->whereBetween('transaction_date', [$from, $to]))
            ->with('journalEntry:id,voucher_number,transaction_date,description,status')
            ->get();
        $debits = $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->debit, 4), '0.0000');
        $credits = $lines->reduce(fn (string $total, JournalLine $line): string => bcadd($total, (string) $line->credit, 4), '0.0000');

        return [
            'opening_balance' => $opening,
            'debit_total' => $debits,
            'credit_total' => $credits,
            'closing_balance' => bcadd($opening, bcsub($credits, $debits, 4), 4),
            'lines' => $lines,
        ];
    }

    private function creditNet(Collection $lines): string
    {
        return $lines->reduce(
            fn (string $total, JournalLine $line): string => bcadd($total, bcsub((string) $line->credit, (string) $line->debit, 4), 4),
            '0.0000',
        );
    }
}
