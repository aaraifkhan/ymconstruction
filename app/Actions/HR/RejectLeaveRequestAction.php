<?php

namespace App\Actions\HR;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $actor, string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($actor, $leaveRequest, $reason): LeaveRequest {
            $request = LeaveRequest::query()->whereKey($leaveRequest)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('reject', $request);

            if (! in_array($request->status, [LeaveRequestStatus::Requested, LeaveRequestStatus::ManagerApproved], true)) {
                throw ValidationException::withMessages(['status' => 'Only pending leave requests can be rejected.']);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages(['decision_reason' => 'A rejection reason is required.']);
            }

            if ((int) $request->requested_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['hr_decided_by_id' => 'The requester cannot reject their own leave.']);
            }

            $request->update([
                'status' => LeaveRequestStatus::Rejected,
                'hr_decided_by_id' => $actor->getKey(),
                'hr_decided_at' => now(),
                'decision_reason' => $reason,
            ]);

            return $request->refresh();
        }, 3);
    }
}
