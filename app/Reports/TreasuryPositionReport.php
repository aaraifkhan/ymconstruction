<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TreasuryPositionReport
{
    /** @return Collection<int, array{mapping:AccountingMapping, balance:string}> */
    public function forCompany(Company $company, CarbonInterface $asOf): Collection
    {
        return AccountingMapping::query()->whereBelongsTo($company)
            ->where(function ($query): void {
                $query->where('system_key', AccountingMappingKey::DefaultCash)
                    ->orWhereNotNull('company_bank_account_id');
            })
            ->where('is_active', true)->with(['account', 'bankAccount'])->get()
            ->map(function (AccountingMapping $mapping) use ($asOf): array {
                $lines = JournalLine::query()->where('company_id', $mapping->company_id)
                    ->where('account_id', $mapping->account_id)
                    ->whereHas('journalEntry', fn ($query) => $query
                        ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                        ->whereDate('transaction_date', '<=', $asOf));

                return [
                    'mapping' => $mapping,
                    'balance' => bcsub((string) (clone $lines)->sum('debit'), (string) (clone $lines)->sum('credit'), 4),
                ];
            })->values();
    }
}
