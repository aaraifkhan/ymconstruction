<?php

namespace App\Policies;

use App\Models\User;
use App\Models\YearEndClosing;

class YearEndClosingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'YearEndClosing';

    public function update(User $user, mixed $record): bool
    {
        return false;
    }

    public function delete(User $user, mixed $record): bool
    {
        return false;
    }

    public function approve(User $user, YearEndClosing $closing): bool
    {
        return $this->workflow($user, $closing, 'Approve');
    }

    public function post(User $user, YearEndClosing $closing): bool
    {
        return $this->workflow($user, $closing, 'Post');
    }

    public function reverse(User $user, YearEndClosing $closing): bool
    {
        return $this->workflow($user, $closing, 'Reverse');
    }

    private function workflow(User $user, YearEndClosing $closing, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:YearEndClosing")
            && $this->canAccessRecord($user, $closing);
    }
}
