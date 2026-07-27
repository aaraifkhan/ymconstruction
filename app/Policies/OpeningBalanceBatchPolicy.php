<?php

namespace App\Policies;

use App\Enums\OpeningBalanceStatus;
use App\Models\OpeningBalanceBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OpeningBalanceBatchPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'OpeningBalanceBatch';

    public function update(User $user, Model $record): bool
    {
        return $record instanceof OpeningBalanceBatch && $record->status === OpeningBalanceStatus::Draft && parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $record instanceof OpeningBalanceBatch && $record->status === OpeningBalanceStatus::Draft && parent::delete($user, $record);
    }

    public function validate(User $user, OpeningBalanceBatch $batch): bool
    {
        return $this->workflow($user, $batch, 'Validate');
    }

    public function post(User $user, OpeningBalanceBatch $batch): bool
    {
        return $this->workflow($user, $batch, 'Post');
    }

    private function workflow(User $user, OpeningBalanceBatch $batch, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:OpeningBalanceBatch") && $this->canAccessRecord($user, $batch);
    }
}
