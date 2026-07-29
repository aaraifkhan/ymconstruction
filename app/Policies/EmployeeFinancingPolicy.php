<?php

namespace App\Policies;

use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeFinancingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeFinancing';

    public function update(User $user, Model $record): bool
    {
        return parent::update($user, $record)
            && in_array($record->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true);
    }

    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record)
            && in_array($record->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true);
    }

    public function submit(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Submit')
            && in_array($financing->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true);
    }

    public function approve(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Approve')
            && $financing->status === EmployeeFinancingStatus::Requested;
    }

    public function reject(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Reject')
            && $financing->status === EmployeeFinancingStatus::Requested;
    }

    public function disburse(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Disburse')
            && in_array($financing->status, [EmployeeFinancingStatus::Approved, EmployeeFinancingStatus::DisbursementPending], true);
    }

    public function recover(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Recover')
            && $financing->status === EmployeeFinancingStatus::Active;
    }

    public function reschedule(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Reschedule')
            && $financing->status === EmployeeFinancingStatus::Active;
    }

    public function waive(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Waive')
            && $financing->status === EmployeeFinancingStatus::Active;
    }

    public function cancel(User $user, EmployeeFinancing $financing): bool
    {
        return $this->workflow($user, $financing, 'Cancel')
            && in_array($financing->status, [
                EmployeeFinancingStatus::Draft,
                EmployeeFinancingStatus::Rejected,
                EmployeeFinancingStatus::Approved,
            ], true);
    }

    private function workflow(User $user, EmployeeFinancing $financing, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmployeeFinancing")
            && $this->canAccessRecord($user, $financing);
    }
}
