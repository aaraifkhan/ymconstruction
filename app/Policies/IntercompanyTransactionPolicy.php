<?php

namespace App\Policies;

use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class IntercompanyTransactionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'IntercompanyTransaction';

    public function view(User $user, Model $record): bool
    {
        return $record instanceof IntercompanyTransaction
            && $this->hasPermission($user, 'View:IntercompanyTransaction')
            && ($user->canAccessTenant($record->company) || $user->canAccessTenant($record->counterpartyCompany));
    }

    public function update(User $user, Model $record): bool
    {
        return $record instanceof IntercompanyTransaction
            && in_array($record->status, [IntercompanyStatus::Draft, IntercompanyStatus::Rejected], true)
            && parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $record instanceof IntercompanyTransaction
            && in_array($record->status, [IntercompanyStatus::Draft, IntercompanyStatus::Rejected], true)
            && parent::delete($user, $record);
    }

    public function submit(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->workflow($user, $transaction, 'Submit');
    }

    public function approveOrigin(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'ApproveOrigin:IntercompanyTransaction')
            && $user->canAccessTenant($transaction->company);
    }

    public function approveCounterparty(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'ApproveCounterparty:IntercompanyTransaction')
            && $user->canAccessTenant($transaction->counterpartyCompany);
    }

    public function reject(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'Reject:IntercompanyTransaction')
            && ($user->canAccessTenant($transaction->company) || $user->canAccessTenant($transaction->counterpartyCompany));
    }

    public function post(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'Post:IntercompanyTransaction')
            && $user->canAccessTenant($transaction->company)
            && $user->canAccessTenant($transaction->counterpartyCompany);
    }

    public function reverse(User $user, IntercompanyTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'Reverse:IntercompanyTransaction')
            && $user->canAccessTenant($transaction->company)
            && $user->canAccessTenant($transaction->counterpartyCompany);
    }

    private function workflow(User $user, IntercompanyTransaction $transaction, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:IntercompanyTransaction")
            && $user->canAccessTenant($transaction->company);
    }
}
