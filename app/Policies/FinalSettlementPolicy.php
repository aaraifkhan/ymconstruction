<?php

namespace App\Policies;

use App\Enums\FinalSettlementStatus;
use App\Models\EmploymentSeparation;
use App\Models\FinalSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinalSettlementPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FinalSettlement';

    public function prepare(User $user, EmploymentSeparation $separation): bool
    {
        return $this->hasPermission($user, 'Prepare:FinalSettlement')
            && $this->canAccessRecord($user, $separation);
    }

    public function update(User $user, Model $record): bool
    {
        return parent::update($user, $record) && $record instanceof FinalSettlement && $record->isEditable();
    }

    public function submit(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Submit');
    }

    public function review(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Review');
    }

    public function approve(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Approve');
    }

    public function reject(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Reject');
    }

    public function post(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Post')
            && $record->status === FinalSettlementStatus::Approved;
    }

    public function reverse(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'Reverse')
            && in_array($record->status, [
                FinalSettlementStatus::Posted, FinalSettlementStatus::Settled,
            ], true);
    }

    public function viewAmounts(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'ViewAmounts');
    }

    public function generateLetter(User $user, FinalSettlement $record): bool
    {
        return $this->workflow($user, $record, 'GenerateLetter')
            && in_array($record->status, [
                FinalSettlementStatus::Approved, FinalSettlementStatus::Posted,
                FinalSettlementStatus::Settled,
            ], true);
    }

    private function workflow(User $user, FinalSettlement $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:FinalSettlement")
            && $this->canAccessRecord($user, $record);
    }
}
