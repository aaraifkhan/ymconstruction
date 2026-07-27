<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ItemCategoryPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ItemCategory';

    public function delete(User $user, Model $itemCategory): bool
    {
        return parent::delete($user, $itemCategory)
            && ! $itemCategory->items()->exists()
            && ! $itemCategory->budgetLines()->exists();
    }
}
