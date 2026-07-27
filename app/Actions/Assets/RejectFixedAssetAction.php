<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectFixedAssetAction
{
    public function handle(FixedAsset $asset, User $actor, string $reason): FixedAsset
    {
        Gate::forUser($actor)->authorize('reject', $asset);

        return DB::transaction(function () use ($asset, $actor, $reason): FixedAsset {
            $asset = FixedAsset::query()->whereKey($asset)->lockForUpdate()->firstOrFail();
            if ($asset->status !== AssetStatus::Submitted || blank($reason)
                || (int) $asset->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'status' => 'A submitted asset, reason, and independent reviewer are required.',
                ]);
            }
            $asset->update([
                'status' => AssetStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            activity('fixed_assets')->causedBy($actor)->performedOn($asset)->event('rejected')->log('rejected fixed asset');

            return $asset->refresh();
        }, attempts: 3);
    }
}
