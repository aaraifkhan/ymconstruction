<?php

namespace App\Policies;

use App\Models\EmployeeCodeSequence;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class EmployeeCodeSequencePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeCodeSequence';

    public function create(User $user): bool
    {
        $company = Filament::getTenant();

        return parent::create($user)
            && $company !== null
            && ! EmployeeCodeSequence::query()->whereBelongsTo($company)->exists();
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
