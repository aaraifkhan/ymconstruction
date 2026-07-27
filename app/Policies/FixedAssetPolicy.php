<?php

namespace App\Policies;

use App\Enums\AssetStatus;
use App\Models\FixedAsset;
use App\Models\User;

class FixedAssetPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FixedAsset';

    public function update(User $user, mixed $record): bool
    {
        return parent::update($user, $record)
            && in_array($record->status, [AssetStatus::Draft, AssetStatus::Rejected], true);
    }

    public function delete(User $user, mixed $record): bool
    {
        return parent::delete($user, $record)
            && in_array($record->status, [AssetStatus::Draft, AssetStatus::Rejected], true);
    }

    public function submit(User $user, FixedAsset $asset): bool
    {
        return $this->hasPermission($user, 'Submit:FixedAsset') && $this->canAccessRecord($user, $asset);
    }

    public function approve(User $user, FixedAsset $asset): bool
    {
        return $this->hasPermission($user, 'Approve:FixedAsset') && $this->canAccessRecord($user, $asset);
    }

    public function reject(User $user, FixedAsset $asset): bool
    {
        return $this->hasPermission($user, 'Reject:FixedAsset') && $this->canAccessRecord($user, $asset);
    }

    public function capitalize(User $user, FixedAsset $asset): bool
    {
        return $this->hasPermission($user, 'Capitalize:FixedAsset') && $this->canAccessRecord($user, $asset);
    }

    public function transfer(User $user, FixedAsset $asset): bool
    {
        return $this->hasPermission($user, 'Transfer:FixedAsset') && $this->canAccessRecord($user, $asset) && $asset->status === AssetStatus::Active;
    }
}
