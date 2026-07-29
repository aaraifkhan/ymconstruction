<?php

namespace App\Actions\HR;

use App\Enums\LeaveRequestStatus;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $actor): LeaveRequest
    {
        return DB::transaction(function () use ($actor, $leaveRequest): LeaveRequest {
            $request = LeaveRequest::query()->with(['leaveType', 'documents'])->whereKey($leaveRequest)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $request);

            if ($request->status !== LeaveRequestStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft leave requests can be submitted.']);
            }

            $policy = LeavePolicy::query()
                ->where('company_id', $request->company_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $request->starts_on)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $request->ends_on))
                ->first();

            if ($policy === null) {
                throw ValidationException::withMessages(['leave_policy_id' => 'No active policy covers the requested period.']);
            }

            if ($request->leaveType->requires_attachment && $request->documents->isEmpty()) {
                throw ValidationException::withMessages(['documents' => 'An attachment is required for this leave type.']);
            }

            $request->update([
                'leave_policy_id' => $policy->getKey(),
                'status' => LeaveRequestStatus::Requested,
                'is_paid_snapshot' => $request->leaveType->is_paid,
                'payroll_impact_snapshot' => $request->leaveType->payroll_impact,
                'requested_by_id' => $actor->getKey(),
                'requested_at' => now(),
            ]);

            activity('leave_requests')->causedBy($actor)->performedOn($request)->event('submitted')
                ->withProperties(['company_id' => $request->company_id, 'leave_policy_id' => $policy->getKey()])
                ->log('submitted leave request');

            return $request->refresh();
        }, 3);
    }
}
