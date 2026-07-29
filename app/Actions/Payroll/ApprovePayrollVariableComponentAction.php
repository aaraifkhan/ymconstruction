<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollVariableComponentStatus;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApprovePayrollVariableComponentAction
{
    public function handle(PayrollVariableComponent $component, User $actor): PayrollVariableComponent
    {
        Gate::forUser($actor)->authorize('approve', $component);

        return DB::transaction(function () use ($actor, $component): PayrollVariableComponent {
            $component = PayrollVariableComponent::query()->whereKey($component)->lockForUpdate()->firstOrFail();
            if ($component->status !== PayrollVariableComponentStatus::PendingApproval
                || (int) $component->submitted_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'An independent approver is required for a submitted component.']);
            }
            $component->update([
                'status' => PayrollVariableComponentStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            activity('payroll_variable_components')->causedBy($actor)->performedOn($component)
                ->event('approved')->withProperties([
                    'company_id' => $component->company_id,
                    'source_checksum' => $component->source_checksum,
                ])->log('approved variable Payroll component');

            return $component;
        }, 3);
    }
}
