<?php

namespace App\Policies;

use App\Enums\PayrollVariableComponentStatus;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollVariableComponentPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PayrollVariableComponent';

    public function update(User $user, Model $record): bool
    {
        return parent::update($user, $record)
            && in_array($record->status, [
                PayrollVariableComponentStatus::Draft,
                PayrollVariableComponentStatus::Rejected,
            ], true);
    }

    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record)
            && in_array($record->status, [
                PayrollVariableComponentStatus::Draft,
                PayrollVariableComponentStatus::Rejected,
            ], true);
    }

    public function submit(User $user, PayrollVariableComponent $component): bool
    {
        return $this->workflow($user, $component, 'Submit')
            && in_array($component->status, [
                PayrollVariableComponentStatus::Draft,
                PayrollVariableComponentStatus::Rejected,
            ], true);
    }

    public function approve(User $user, PayrollVariableComponent $component): bool
    {
        return $this->workflow($user, $component, 'Approve')
            && $component->status === PayrollVariableComponentStatus::PendingApproval;
    }

    public function reject(User $user, PayrollVariableComponent $component): bool
    {
        return $this->workflow($user, $component, 'Reject')
            && $component->status === PayrollVariableComponentStatus::PendingApproval;
    }

    private function workflow(User $user, PayrollVariableComponent $component, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:PayrollVariableComponent")
            && $this->canAccessRecord($user, $component);
    }
}
