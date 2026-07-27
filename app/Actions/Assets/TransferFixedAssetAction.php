<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\AssetTransfer;
use App\Models\FixedAsset;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransferFixedAssetAction
{
    /** @param array{custodian_employment_id:?int,project_id:?int,project_site_id:?int,cost_center_id:?int,location:?string} $destination */
    public function handle(FixedAsset $asset, User $actor, CarbonInterface $date, string $reason, array $destination): AssetTransfer
    {
        Gate::forUser($actor)->authorize('transfer', $asset);

        return DB::transaction(function () use ($asset, $actor, $date, $reason, $destination): AssetTransfer {
            $asset = FixedAsset::query()->whereKey($asset)->lockForUpdate()->firstOrFail();
            if ($asset->status !== AssetStatus::Active || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'Only active assets may be transferred with a reason.']);
            }
            $transfer = $asset->transfers()->create([
                'company_id' => $asset->company_id, 'from_custodian_employment_id' => $asset->custodian_employment_id,
                'to_custodian_employment_id' => $destination['custodian_employment_id'], 'from_project_id' => $asset->project_id,
                'to_project_id' => $destination['project_id'], 'from_project_site_id' => $asset->project_site_id,
                'to_project_site_id' => $destination['project_site_id'], 'from_cost_center_id' => $asset->cost_center_id,
                'to_cost_center_id' => $destination['cost_center_id'], 'from_location' => $asset->location,
                'to_location' => $destination['location'], 'effective_on' => $date, 'reason' => $reason, 'transferred_by_id' => $actor->getKey(),
            ]);
            $asset->update($destination);
            activity('fixed_assets')->causedBy($actor)->performedOn($asset)->event('transferred')->withProperties(['asset_transfer_id' => $transfer->getKey()])->log('transferred fixed asset');

            return $transfer;
        }, attempts: 3);
    }
}
