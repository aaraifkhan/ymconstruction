<?php

namespace App\Policies;

use App\Models\JournalLine;
use App\Models\User;

class JournalLinePolicy
{
    public function view(User $user, JournalLine $line): bool
    {
        return $user->can('view', $line->journalEntry);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:JournalLine');
    }

    public function update(User $user, JournalLine $line): bool
    {
        return $line->journalEntry->isEditable() && $user->can('update', $line->journalEntry);
    }

    public function delete(User $user, JournalLine $line): bool
    {
        return $this->update($user, $line);
    }
}
