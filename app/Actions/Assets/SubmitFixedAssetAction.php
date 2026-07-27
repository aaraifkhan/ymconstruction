<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitFixedAssetAction
{
    public function handle(FixedAsset $asset, User $actor): FixedAsset
    {
        Gate::forUser($actor)->authorize('submit', $asset);

        return DB::transaction(function () use ($asset, $actor): FixedAsset {
            $asset = FixedAsset::query()->whereKey($asset)->lockForUpdate()->firstOrFail();
            if (! in_array($asset->status, [AssetStatus::Draft, AssetStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected assets may be submitted.']);
            }
            if ((int) $asset->prepared_by_id !== (int) $actor->getKey()) {
                throw ValidationException::withMessages(['prepared_by_id' => 'Only the preparer may submit the asset.']);
            }
            $asset->update(['status' => AssetStatus::Submitted, 'submitted_by_id' => $actor->getKey(), 'submitted_at' => now()]);
            activity('fixed_assets')->causedBy($actor)->performedOn($asset)->event('submitted')->log('submitted fixed asset');

            return $asset->refresh();
        }, attempts: 3);
    }
}
