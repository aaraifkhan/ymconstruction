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

class ApproveLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $actor): LeaveRequest
    {
        return DB::transaction(function () use ($actor, $leaveRequest): LeaveRequest {
            $request = LeaveRequest::query()->with('leavePolicy')->whereKey($leaveRequest)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('approve', $request);

            if ($request->status !== LeaveRequestStatus::ManagerApproved) {
                throw ValidationException::withMessages(['status' => 'Manager approval is required before HR approval.']);
            }

            if (in_array((int) $actor->getKey(), [(int) $request->requested_by_id, (int) $request->manager_decided_by_id], true)) {
                throw ValidationException::withMessages(['hr_decided_by_id' => 'HR approval must be independent of earlier decisions.']);
            }

            if ($request->leavePolicy === null) {
                throw ValidationException::withMessages(['leave_policy_id' => 'The effective leave policy is unavailable.']);
            }

            $currentBalance = (float) LeaveLedgerEntry::query()
                ->where('company_id', $request->company_id)
                ->where('employment_id', $request->employment_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->lockForUpdate()
                ->get()
                ->sum('units');
            $resultingBalance = $currentBalance - (float) $request->requested_units;

            if ($resultingBalance < 0 && ! $request->leavePolicy->allow_negative_balance) {
                throw ValidationException::withMessages(['requested_units' => 'The request exceeds the available leave balance.']);
            }

            LeaveLedgerEntry::query()->create([
                'company_id' => $request->company_id,
                'employment_id' => $request->employment_id,
                'leave_type_id' => $request->leave_type_id,
                'entry_type' => LeaveLedgerEntryType::Consumption,
                'effective_on' => $request->starts_on,
                'units' => -1 * (float) $request->requested_units,
                'source_type' => LeaveRequest::class,
                'source_id' => $request->getKey(),
                'reason' => 'Approved leave request consumption',
                'recorded_by_id' => $actor->getKey(),
            ]);

            $request->update([
                'status' => LeaveRequestStatus::Approved,
                'hr_decided_by_id' => $actor->getKey(),
                'hr_decided_at' => now(),
            ]);

            activity('leave_requests')->causedBy($actor)->performedOn($request)->event('approved')
                ->withProperties(['company_id' => $request->company_id, 'balance_before' => $currentBalance, 'balance_after' => $resultingBalance])
                ->log('approved leave request');

            return $request->refresh();
        }, 3);
    }
}
