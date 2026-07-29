<?php

namespace App\Actions\HR;

use App\Enums\AttendanceCorrectionStatus;
use App\Enums\AttendanceDayStatus;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApproveAttendanceCorrectionAction
{
    public function handle(AttendanceCorrection $correction, User $actor, bool $approve = true, ?string $decisionReason = null): AttendanceCorrection
    {
        return DB::transaction(function () use ($actor, $approve, $correction, $decisionReason): AttendanceCorrection {
            $lockedCorrection = AttendanceCorrection::query()->whereKey($correction)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('approve', $lockedCorrection);

            if ($lockedCorrection->status !== AttendanceCorrectionStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending corrections can be decided.']);
            }

            if ((int) $lockedCorrection->requested_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['decided_by_id' => 'A correction requester cannot approve their own correction.']);
            }

            if (! $approve && blank($decisionReason)) {
                throw ValidationException::withMessages(['decision_reason' => 'A rejection reason is required.']);
            }

            if ($approve) {
                $allowed = [
                    'day_status', 'first_in_at', 'last_out_at', 'scheduled_minutes',
                    'worked_minutes', 'late_minutes', 'overtime_minutes', 'notes',
                ];
                $changes = Validator::make(
                    Arr::only($lockedCorrection->proposed_snapshot, $allowed),
                    [
                        'day_status' => ['sometimes', Rule::enum(AttendanceDayStatus::class)],
                        'first_in_at' => ['sometimes', 'nullable', 'date'],
                        'last_out_at' => ['sometimes', 'nullable', 'date', 'after:first_in_at'],
                        'scheduled_minutes' => ['sometimes', 'integer', 'min:0'],
                        'worked_minutes' => ['sometimes', 'integer', 'min:0'],
                        'late_minutes' => ['sometimes', 'integer', 'min:0'],
                        'overtime_minutes' => ['sometimes', 'integer', 'min:0'],
                        'notes' => ['sometimes', 'nullable', 'string'],
                    ],
                )->validate();

                if ($changes === []) {
                    throw ValidationException::withMessages(['proposed_snapshot' => 'At least one valid attendance change is required.']);
                }

                $changes['source_checksum'] = hash('sha256', json_encode([
                    'attendance_record_id' => $lockedCorrection->attendance_record_id,
                    'correction_id' => $lockedCorrection->getKey(),
                    'changes' => $changes,
                ], JSON_THROW_ON_ERROR));
                $changes['updated_at'] = now();

                DB::table('attendance_records')
                    ->where('id', $lockedCorrection->attendance_record_id)
                    ->update($changes);
            }

            DB::table('attendance_corrections')->where('id', $lockedCorrection->getKey())->update([
                'status' => $approve ? AttendanceCorrectionStatus::Approved->value : AttendanceCorrectionStatus::Rejected->value,
                'decided_by_id' => $actor->getKey(),
                'decided_at' => now(),
                'decision_reason' => $decisionReason,
                'updated_at' => now(),
            ]);

            activity('attendance_corrections')
                ->causedBy($actor)
                ->performedOn($lockedCorrection)
                ->event($approve ? 'approved' : 'rejected')
                ->withProperties(['company_id' => $lockedCorrection->company_id])
                ->log(($approve ? 'approved' : 'rejected').' attendance correction');

            return $lockedCorrection->refresh();
        }, 3);
    }
}
