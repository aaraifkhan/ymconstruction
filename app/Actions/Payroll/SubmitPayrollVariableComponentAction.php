<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollVariableComponentStatus;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitPayrollVariableComponentAction
{
    public function handle(PayrollVariableComponent $component, User $actor): PayrollVariableComponent
    {
        Gate::forUser($actor)->authorize('submit', $component);

        return DB::transaction(function () use ($actor, $component): PayrollVariableComponent {
            $component = PayrollVariableComponent::query()->whereKey($component)->lockForUpdate()->firstOrFail();
            if (! in_array($component->status, [
                PayrollVariableComponentStatus::Draft,
                PayrollVariableComponentStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected components may be submitted.']);
            }
            $component->update([
                'status' => PayrollVariableComponentStatus::PendingApproval,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            activity('payroll_variable_components')->causedBy($actor)->performedOn($component)
                ->event('submitted')->withProperties(['company_id' => $component->company_id])
                ->log('submitted variable Payroll component');

            return $component;
        }, 3);
    }
}
