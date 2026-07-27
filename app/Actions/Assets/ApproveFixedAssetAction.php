<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveFixedAssetAction
{
    public function handle(FixedAsset $asset, User $actor): FixedAsset
    {
        Gate::forUser($actor)->authorize('approve', $asset);

        return DB::transaction(function () use ($asset, $actor): FixedAsset {
            $asset = FixedAsset::query()->whereKey($asset)->lockForUpdate()->firstOrFail();
            if ($asset->status !== AssetStatus::Submitted || (int) $asset->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Submitted assets require an independent approver.']);
            }
            $asset->update(['status' => AssetStatus::Approved, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);
            activity('fixed_assets')->causedBy($actor)->performedOn($asset)->event('approved')->log('approved fixed asset');

            return $asset->refresh();
        }, attempts: 3);
    }
}
