<?php

namespace App\Policies;

use App\Enums\TreasuryStatus;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TreasuryTransactionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'TreasuryTransaction';

    public function update(User $user, Model $transaction): bool
    {
        return parent::update($user, $transaction) && $transaction->isEditable();
    }

    public function delete(User $user, Model $transaction): bool
    {
        return parent::delete($user, $transaction) && $transaction->isEditable();
    }

    public function submit(User $user, TreasuryTransaction $transaction): bool
    {
        return $this->canPerform($user, $transaction, 'Submit:TreasuryTransaction')
            && $transaction->isEditable();
    }

    public function approve(User $user, TreasuryTransaction $transaction): bool
    {
        return $this->canPerform($user, $transaction, 'Approve:TreasuryTransaction')
            && $transaction->status === TreasuryStatus::Submitted;
    }

    public function reject(User $user, TreasuryTransaction $transaction): bool
    {
        return $this->canPerform($user, $transaction, 'Reject:TreasuryTransaction')
            && in_array($transaction->status, [TreasuryStatus::Submitted, TreasuryStatus::Approved], true);
    }

    public function post(User $user, TreasuryTransaction $transaction): bool
    {
        return $this->canPerform($user, $transaction, 'Post:TreasuryTransaction')
            && $transaction->status === TreasuryStatus::Approved;
    }

    public function reverse(User $user, TreasuryTransaction $transaction): bool
    {
        return $this->canPerform($user, $transaction, 'Reverse:TreasuryTransaction')
            && $transaction->status === TreasuryStatus::Posted;
    }

    private function canPerform(User $user, TreasuryTransaction $transaction, string $permission): bool
    {
        return $this->hasPermission($user, $permission) && $this->canAccessRecord($user, $transaction);
    }
}
