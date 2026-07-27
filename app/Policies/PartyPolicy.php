<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PartyPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'Party';

    public function delete(User $user, Model $party): bool
    {
        return parent::delete($user, $party)
            && ! $party->clientProjects()->exists()
            && ! $party->consultantProjects()->exists();
    }
}
