<?php

namespace App\Actions\Assets;

use App\Enums\AssetAccountingStatus;
use App\Enums\AssetStatus;
use App\Enums\FinancialPeriodStatus;
use App\Models\DepreciationRun;
use App\Models\FinancialPeriod;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GenerateDepreciationRunAction
{
    public function handle(DepreciationRun $run, User $actor): DepreciationRun
    {
        Gate::forUser($actor)->authorize('generate', $run);

        return DB::transaction(function () use ($run): DepreciationRun {
            $run = DepreciationRun::query()->whereKey($run)->lockForUpdate()->firstOrFail();
            if ($run->status !== AssetAccountingStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft runs may be generated.']);
            }
            $period = FinancialPeriod::query()->whereKey($run->financial_period_id)->where('company_id', $run->company_id)->where('status', FinancialPeriodStatus::Open)->first();
            if ($period === null || ! $run->depreciation_date->betweenIncluded($period->starts_on, $period->ends_on)) {
                throw ValidationException::withMessages(['financial_period_id' => 'Choose an open company period containing the depreciation date.']);
            }
            $run->lines()->delete();
            $total = '0.0000';
            FixedAsset::query()->where('company_id', $run->company_id)->where('status', AssetStatus::Active)
                ->whereDate('available_for_use_on', '<=', $period->ends_on)->with('category')->lockForUpdate()->get()
                ->each(function (FixedAsset $asset) use ($run, &$total): void {
                    if (! $asset->category->is_depreciable) {
                        return;
                    }
                    $remaining = bcsub(bcsub((string) $asset->acquisition_cost, (string) $asset->residual_value, 4), (string) $asset->accumulated_depreciation, 4);
                    $monthly = bcdiv(bcsub((string) $asset->acquisition_cost, (string) $asset->residual_value, 4), (string) $asset->useful_life_months, 4);
                    $amount = bccomp($remaining, $monthly, 4) === -1 ? $remaining : $monthly;
                    if (bccomp($amount, '0', 4) !== 1) {
                        return;
                    }
                    $closing = bcadd((string) $asset->accumulated_depreciation, $amount, 4);
                    $run->lines()->create([
                        'company_id' => $asset->company_id, 'fixed_asset_id' => $asset->getKey(),
                        'expense_account_id' => $asset->category->depreciation_expense_account_id,
                        'accumulated_depreciation_account_id' => $asset->category->accumulated_depreciation_account_id,
                        'project_id' => $asset->project_id, 'project_site_id' => $asset->project_site_id, 'cost_center_id' => $asset->cost_center_id,
                        'opening_accumulated_depreciation' => $asset->accumulated_depreciation,
                        'depreciation_amount' => $amount, 'closing_accumulated_depreciation' => $closing,
                        'closing_carrying_amount' => bcsub((string) $asset->acquisition_cost, $closing, 4),
                    ]);
                    $total = bcadd($total, $amount, 4);
                });
            if (bccomp($total, '0', 4) !== 1) {
                throw ValidationException::withMessages(['assets' => 'No eligible depreciation exists for this period.']);
            }
            $run->update(['total_amount' => $total]);

            return $run->load('lines');
        }, attempts: 3);
    }
}
