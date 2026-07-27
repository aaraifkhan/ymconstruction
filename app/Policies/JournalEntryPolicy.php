<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JournalEntryPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'JournalEntry';

    public function update(User $user, Model $record): bool
    {
        return $record instanceof JournalEntry && $record->isEditable() && parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $record instanceof JournalEntry && $record->isEditable() && parent::delete($user, $record);
    }

    public function submit(User $user, JournalEntry $entry): bool
    {
        return $this->workflow($user, $entry, 'Submit');
    }

    public function approve(User $user, JournalEntry $entry): bool
    {
        return $this->workflow($user, $entry, 'Approve');
    }

    public function reject(User $user, JournalEntry $entry): bool
    {
        return $this->workflow($user, $entry, 'Reject');
    }

    public function post(User $user, JournalEntry $entry): bool
    {
        return $this->workflow($user, $entry, 'Post');
    }

    public function reverse(User $user, JournalEntry $entry): bool
    {
        return $this->workflow($user, $entry, 'Reverse');
    }

    private function workflow(User $user, JournalEntry $entry, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:JournalEntry") && $this->canAccessRecord($user, $entry);
    }
}
