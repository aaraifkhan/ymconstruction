<?php

namespace App\Policies;

use App\Models\OpeningBalanceLine;
use App\Models\User;

class OpeningBalanceLinePolicy
{
    public function view(User $user, OpeningBalanceLine $line): bool
    {
        return $user->can('view', $line->batch);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:OpeningBalanceLine');
    }

    public function update(User $user, OpeningBalanceLine $line): bool
    {
        return $user->can('update', $line->batch);
    }

    public function delete(User $user, OpeningBalanceLine $line): bool
    {
        return $this->update($user, $line);
    }
}
