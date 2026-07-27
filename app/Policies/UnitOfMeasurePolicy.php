<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasurePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'UnitOfMeasure';

    public function delete(User $user, Model $unitOfMeasure): bool
    {
        return parent::delete($user, $unitOfMeasure) && ! $unitOfMeasure->items()->exists();
    }
}
