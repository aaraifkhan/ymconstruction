<?php

namespace App\Policies;

use App\Enums\AssetAccountingStatus;
use App\Models\AssetDisposal;
use App\Models\User;

class AssetDisposalPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AssetDisposal';

    public function update(User $user, mixed $record): bool
    {
        return parent::update($user, $record) && $record->status === AssetAccountingStatus::Draft;
    }

    public function delete(User $user, mixed $record): bool
    {
        return parent::delete($user, $record) && $record->status === AssetAccountingStatus::Draft;
    }

    public function approve(User $user, AssetDisposal $disposal): bool
    {
        return $this->hasPermission($user, 'Approve:AssetDisposal') && $this->canAccessRecord($user, $disposal);
    }

    public function post(User $user, AssetDisposal $disposal): bool
    {
        return $this->hasPermission($user, 'Post:AssetDisposal') && $this->canAccessRecord($user, $disposal);
    }

    public function reverse(User $user, AssetDisposal $disposal): bool
    {
        return $this->hasPermission($user, 'Reverse:AssetDisposal') && $this->canAccessRecord($user, $disposal);
    }
}
