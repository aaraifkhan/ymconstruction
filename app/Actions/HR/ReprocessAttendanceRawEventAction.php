<?php

namespace App\Actions\HR;

use App\Models\AttendanceRawEvent;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ReprocessAttendanceRawEventAction
{
    public function __construct(
        private NormalizeAttendanceRawEventAction $normalizeRawEvent,
    ) {}

    public function handle(AttendanceRawEvent $rawEvent, User $actor): AttendanceRawEvent
    {
        Gate::forUser($actor)->authorize('reprocess', $rawEvent);

        $event = $this->normalizeRawEvent->handle($rawEvent);

        activity('attendance_raw_events')->causedBy($actor)->performedOn($event)->event('reprocessed')
            ->withProperties(['company_id' => $event->company_id, 'processing_status' => $event->processing_status->value])
            ->log('reprocessed raw Attendance event');

        return $event;
    }
}
