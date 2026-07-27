<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class IntercompanyReconciliationReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompanies(Collection $companies, CarbonInterface $asOf): Collection
    {
        $companyIds = $companies->pluck('id')->all();
        $accounts = Account::query()->whereIn('company_id', $companyIds)
            ->whereIn('system_key', [
                AccountingMappingKey::DueFromRelatedCompanies,
                AccountingMappingKey::DueToRelatedCompanies,
            ])->get()->keyBy('id');
        $lines = JournalLine::query()->whereIn('company_id', $companyIds)
            ->whereIn('related_company_id', $companyIds)
            ->whereIn('account_id', $accounts->keys())
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
                ->whereDate('transaction_date', '<=', $asOf))
            ->get();

        return $lines->groupBy(function (JournalLine $line): string {
            $ids = [(int) $line->company_id, (int) $line->related_company_id];
            sort($ids);

            return implode(':', $ids);
        })->map(function (Collection $pairLines, string $pair) use ($accounts, $companies): array {
            [$firstId, $secondId] = array_map('intval', explode(':', $pair));
            $dueFrom = '0.0000';
            $dueTo = '0.0000';
            foreach ($pairLines as $line) {
                $net = bcsub((string) $line->debit, (string) $line->credit, 4);
                if ($accounts[$line->account_id]->system_key === AccountingMappingKey::DueFromRelatedCompanies->value) {
                    $dueFrom = bcadd($dueFrom, $net, 4);
                } else {
                    $dueTo = bcadd($dueTo, bcmul($net, '-1', 4), 4);
                }
            }
            $difference = bcsub($dueFrom, $dueTo, 4);

            return [
                'first_company_id' => $firstId,
                'first_company' => $companies->firstWhere('id', $firstId)?->name,
                'second_company_id' => $secondId,
                'second_company' => $companies->firstWhere('id', $secondId)?->name,
                'due_from' => $dueFrom,
                'due_to' => $dueTo,
                'difference' => $difference,
                'reconciles' => bccomp($difference, '0.0000', 4) === 0,
            ];
        })->values();
    }
}
