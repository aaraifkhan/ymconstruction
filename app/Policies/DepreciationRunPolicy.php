<?php

namespace App\Policies;

use App\Enums\AssetAccountingStatus;
use App\Models\DepreciationRun;
use App\Models\User;

class DepreciationRunPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'DepreciationRun';

    public function update(User $user, mixed $record): bool
    {
        return parent::update($user, $record) && $record->status === AssetAccountingStatus::Draft;
    }

    public function delete(User $user, mixed $record): bool
    {
        return parent::delete($user, $record) && $record->status === AssetAccountingStatus::Draft;
    }

    public function generate(User $user, DepreciationRun $run): bool
    {
        return $this->hasPermission($user, 'Generate:DepreciationRun') && $this->canAccessRecord($user, $run);
    }

    public function submit(User $user, DepreciationRun $run): bool
    {
        return $this->hasPermission($user, 'Submit:DepreciationRun') && $this->canAccessRecord($user, $run);
    }

    public function approve(User $user, DepreciationRun $run): bool
    {
        return $this->hasPermission($user, 'Approve:DepreciationRun') && $this->canAccessRecord($user, $run);
    }

    public function post(User $user, DepreciationRun $run): bool
    {
        return $this->hasPermission($user, 'Post:DepreciationRun') && $this->canAccessRecord($user, $run);
    }

    public function reverse(User $user, DepreciationRun $run): bool
    {
        return $this->hasPermission($user, 'Reverse:DepreciationRun') && $this->canAccessRecord($user, $run);
    }
}
