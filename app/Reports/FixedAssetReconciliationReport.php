<?php

namespace App\Reports;

use App\Enums\AssetStatus;
use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalLine;
use Illuminate\Support\Collection;

class FixedAssetReconciliationReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return $company->assetCategories()
            ->with(['costAccount', 'accumulatedDepreciationAccount'])
            ->get()
            ->groupBy(fn ($category): string => $category->cost_account_id.'|'.$category->accumulated_depreciation_account_id)
            ->map(function (Collection $categories) use ($company): array {
                $costAccount = $categories->first()->costAccount;
                $contraAccount = $categories->first()->accumulatedDepreciationAccount;
                $assets = $company->fixedAssets()
                    ->whereIn('asset_category_id', $categories->modelKeys())
                    ->where('status', AssetStatus::Active)
                    ->get();
                $registerCost = number_format((float) $assets->sum('acquisition_cost'), 4, '.', '');
                $registerAccumulated = number_format((float) $assets->sum('accumulated_depreciation'), 4, '.', '');
                $glCost = $this->accountBalance($company, $costAccount->getKey());
                $glAccumulated = $contraAccount === null
                    ? '0.0000'
                    : bcmul($this->accountBalance($company, $contraAccount->getKey()), '-1', 4);

                return [
                    'categories' => $categories->pluck('name')->join(', '),
                    'cost_account' => $costAccount,
                    'accumulated_account' => $contraAccount,
                    'register_cost' => $registerCost,
                    'gl_cost' => $glCost,
                    'cost_difference' => bcsub($registerCost, $glCost, 4),
                    'register_accumulated' => $registerAccumulated,
                    'gl_accumulated' => $glAccumulated,
                    'accumulated_difference' => bcsub($registerAccumulated, $glAccumulated, 4),
                    'reconciled' => bccomp($registerCost, $glCost, 4) === 0
                        && bccomp($registerAccumulated, $glAccumulated, 4) === 0,
                ];
            })
            ->values();
    }

    private function accountBalance(Company $company, int $accountId): string
    {
        $lines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', [
                JournalStatus::Posted->value,
                JournalStatus::Reversed->value,
            ]));

        return bcsub((string) (clone $lines)->sum('debit'), (string) (clone $lines)->sum('credit'), 4);
    }
}
