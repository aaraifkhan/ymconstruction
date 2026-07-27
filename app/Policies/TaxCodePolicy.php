<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaxCodePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'TaxCode';

    public function delete(User $user, Model $taxCode): bool
    {
        return parent::delete($user, $taxCode) && ! $taxCode->defaultForItems()->exists();
    }
}
