<?php

namespace App\Actions\HR;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManagerApproveLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $actor): LeaveRequest
    {
        return DB::transaction(function () use ($actor, $leaveRequest): LeaveRequest {
            $request = LeaveRequest::query()->whereKey($leaveRequest)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('managerApprove', $request);

            if ($request->status !== LeaveRequestStatus::Requested) {
                throw ValidationException::withMessages(['status' => 'Only requested leave can receive manager approval.']);
            }

            if ((int) $request->requested_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['manager_decided_by_id' => 'The requester cannot approve their own leave.']);
            }

            $request->update([
                'status' => LeaveRequestStatus::ManagerApproved,
                'manager_decided_by_id' => $actor->getKey(),
                'manager_decided_at' => now(),
            ]);

            return $request->refresh();
        }, 3);
    }
}
