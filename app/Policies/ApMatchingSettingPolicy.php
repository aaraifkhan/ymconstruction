<?php

namespace App\Policies;

use App\Models\ApMatchingSetting;
use App\Models\User;
use Filament\Facades\Filament;

class ApMatchingSettingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ApMatchingSetting';

    public function create(User $user): bool
    {
        return parent::create($user)
            && ! ApMatchingSetting::query()->whereBelongsTo(Filament::getTenant())->exists();
    }
}
