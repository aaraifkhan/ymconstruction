<?php

namespace App\Actions\HR;

use App\Enums\AttendancePunchStatus;
use App\Models\AttendancePunch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveAttendancePunchAction
{
    public function handle(AttendancePunch $punch, User $actor, bool $approve = true, ?string $rejectionReason = null): AttendancePunch
    {
        return DB::transaction(function () use ($actor, $approve, $punch, $rejectionReason): AttendancePunch {
            $lockedPunch = AttendancePunch::query()->whereKey($punch)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('approve', $lockedPunch);

            if ($lockedPunch->status !== AttendancePunchStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending punches can be decided.']);
            }

            if ((int) $lockedPunch->created_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'A punch creator cannot approve their own evidence.']);
            }

            if (! $approve && blank($rejectionReason)) {
                throw ValidationException::withMessages(['rejection_reason' => 'A rejection reason is required.']);
            }

            DB::table('attendance_punches')->where('id', $lockedPunch->getKey())->update([
                'status' => $approve ? AttendancePunchStatus::Approved->value : AttendancePunchStatus::Rejected->value,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
                'rejection_reason' => $approve ? null : $rejectionReason,
                'updated_at' => now(),
            ]);

            activity('attendance_punches')
                ->causedBy($actor)
                ->performedOn($lockedPunch)
                ->event($approve ? 'approved' : 'rejected')
                ->withProperties(['company_id' => $lockedPunch->company_id])
                ->log(($approve ? 'approved' : 'rejected').' manual attendance punch');

            return $lockedPunch->refresh();
        }, 3);
    }
}
