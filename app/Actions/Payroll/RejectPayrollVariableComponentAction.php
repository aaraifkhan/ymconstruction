<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollVariableComponentStatus;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectPayrollVariableComponentAction
{
    public function handle(PayrollVariableComponent $component, User $actor, string $reason): PayrollVariableComponent
    {
        Gate::forUser($actor)->authorize('reject', $component);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($actor, $component, $reason): PayrollVariableComponent {
            $component = PayrollVariableComponent::query()->whereKey($component)->lockForUpdate()->firstOrFail();
            if ($component->status !== PayrollVariableComponentStatus::PendingApproval) {
                throw ValidationException::withMessages(['status' => 'Only a submitted component may be rejected.']);
            }
            $component->update([
                'status' => PayrollVariableComponentStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            activity('payroll_variable_components')->causedBy($actor)->performedOn($component)
                ->event('rejected')->withProperties(['company_id' => $component->company_id, 'reason' => $reason])
                ->log('rejected variable Payroll component');

            return $component;
        }, 3);
    }
}
