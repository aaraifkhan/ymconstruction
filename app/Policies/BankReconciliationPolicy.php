<?php

namespace App\Policies;

use App\Enums\BankReconciliationStatus;
use App\Models\BankReconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'BankReconciliation';

    public function update(User $user, Model $reconciliation): bool
    {
        return parent::update($user, $reconciliation) && $reconciliation->isOpen();
    }

    public function delete(User $user, Model $reconciliation): bool
    {
        return parent::delete($user, $reconciliation)
            && $reconciliation->status === BankReconciliationStatus::Draft;
    }

    public function match(User $user, BankReconciliation $reconciliation): bool
    {
        return $this->canPerform($user, $reconciliation, 'Match:BankReconciliation')
            && $reconciliation->isOpen();
    }

    public function unmatch(User $user, BankReconciliation $reconciliation): bool
    {
        return $this->canPerform($user, $reconciliation, 'Unmatch:BankReconciliation')
            && $reconciliation->isOpen();
    }

    public function adjust(User $user, BankReconciliation $reconciliation): bool
    {
        return $this->canPerform($user, $reconciliation, 'Adjust:BankReconciliation')
            && $reconciliation->isOpen();
    }

    public function close(User $user, BankReconciliation $reconciliation): bool
    {
        return $this->canPerform($user, $reconciliation, 'Close:BankReconciliation')
            && $reconciliation->isOpen();
    }

    public function reopen(User $user, BankReconciliation $reconciliation): bool
    {
        return $this->canPerform($user, $reconciliation, 'Reopen:BankReconciliation')
            && $reconciliation->status === BankReconciliationStatus::Closed;
    }

    private function canPerform(User $user, BankReconciliation $reconciliation, string $permission): bool
    {
        return $this->hasPermission($user, $permission) && $this->canAccessRecord($user, $reconciliation);
    }
}
