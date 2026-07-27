<?php

namespace App\Actions\Assets;

use App\Enums\AssetAccountingStatus;
use App\Enums\AssetStatus;
use App\Models\AssetDisposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveAssetDisposalAction
{
    public function handle(AssetDisposal $disposal, User $actor): AssetDisposal
    {
        Gate::forUser($actor)->authorize('approve', $disposal);

        return DB::transaction(function () use ($disposal, $actor): AssetDisposal {
            $disposal = AssetDisposal::query()->with('fixedAsset')->whereKey($disposal)->lockForUpdate()->firstOrFail();
            if ($disposal->status !== AssetAccountingStatus::Draft || $disposal->fixedAsset->status !== AssetStatus::Active || (int) $disposal->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Active asset disposal requires an independent approver.']);
            }
            $asset = $disposal->fixedAsset;
            $carrying = $asset->carryingAmount();
            $gain = bccomp((string) $disposal->proceeds_amount, $carrying, 4) === 1 ? bcsub((string) $disposal->proceeds_amount, $carrying, 4) : '0.0000';
            $loss = bccomp($carrying, (string) $disposal->proceeds_amount, 4) === 1 ? bcsub($carrying, (string) $disposal->proceeds_amount, 4) : '0.0000';
            $disposal->update(['status' => AssetAccountingStatus::Approved, 'cost_amount' => $asset->acquisition_cost, 'accumulated_depreciation_amount' => $asset->accumulated_depreciation, 'carrying_amount' => $carrying, 'gain_amount' => $gain, 'loss_amount' => $loss, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);

            return $disposal->refresh();
        });
    }
}
