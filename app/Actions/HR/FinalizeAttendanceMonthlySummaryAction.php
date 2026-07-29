<?php

namespace App\Actions\HR;

use App\Enums\AttendanceRecordState;
use App\Enums\AttendanceSummaryStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinalizeAttendanceMonthlySummaryAction
{
    public function handle(AttendanceMonthlySummary $summary, User $actor): AttendanceMonthlySummary
    {
        return DB::transaction(function () use ($actor, $summary): AttendanceMonthlySummary {
            $lockedSummary = AttendanceMonthlySummary::query()->whereKey($summary)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('finalize', $lockedSummary);

            if ($lockedSummary->status === AttendanceSummaryStatus::Finalized) {
                return $lockedSummary;
            }

            $draftCount = AttendanceRecord::query()
                ->where('company_id', $lockedSummary->company_id)
                ->where('employment_id', $lockedSummary->employment_id)
                ->whereBetween('attendance_date', [$lockedSummary->period_start, $lockedSummary->period_end])
                ->where('state', AttendanceRecordState::Draft)
                ->count();

            if ($draftCount > 0) {
                throw ValidationException::withMessages(['status' => 'All daily attendance records in the period must be finalized first.']);
            }

            DB::table('attendance_monthly_summaries')->where('id', $lockedSummary->getKey())->update([
                'status' => AttendanceSummaryStatus::Finalized->value,
                'finalized_by_id' => $actor->getKey(),
                'finalized_at' => now(),
                'updated_at' => now(),
            ]);

            activity('attendance_monthly_summaries')->causedBy($actor)->performedOn($lockedSummary)->event('finalized')
                ->withProperties(['company_id' => $lockedSummary->company_id, 'source_checksum' => $lockedSummary->source_checksum])
                ->log('finalized monthly attendance summary');

            return $lockedSummary->refresh();
        }, 3);
    }
}
