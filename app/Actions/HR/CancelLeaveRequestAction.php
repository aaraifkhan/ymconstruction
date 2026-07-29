<?php

namespace App\Actions\HR;

use App\Enums\LeaveLedgerEntryType;
use App\Enums\LeaveRequestStatus;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $actor, string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($actor, $leaveRequest, $reason): LeaveRequest {
            $request = LeaveRequest::query()->whereKey($leaveRequest)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('cancel', $request);

            if (! in_array($request->status, [LeaveRequestStatus::Requested, LeaveRequestStatus::ManagerApproved, LeaveRequestStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'This leave request cannot be cancelled.']);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages(['decision_reason' => 'A cancellation reason is required.']);
            }

            if ($request->status === LeaveRequestStatus::Approved) {
                LeaveLedgerEntry::query()->create([
                    'company_id' => $request->company_id,
                    'employment_id' => $request->employment_id,
                    'leave_type_id' => $request->leave_type_id,
                    'entry_type' => LeaveLedgerEntryType::Reversal,
                    'effective_on' => now()->toDateString(),
                    'units' => $request->requested_units,
                    'source_type' => LeaveRequest::class,
                    'source_id' => $request->getKey(),
                    'reason' => 'Cancelled leave request reversal: '.$reason,
                    'recorded_by_id' => $actor->getKey(),
                ]);
            }

            DB::table('leave_requests')->where('id', $request->getKey())->update([
                'status' => LeaveRequestStatus::Cancelled->value,
                'cancelled_by_id' => $actor->getKey(),
                'cancelled_at' => now(),
                'decision_reason' => $reason,
                'updated_at' => now(),
            ]);

            activity('leave_requests')->causedBy($actor)->performedOn($request)->event('cancelled')
                ->withProperties(['company_id' => $request->company_id, 'reason' => $reason])
                ->log('cancelled leave request');

            return $request->refresh();
        }, 3);
    }
}
