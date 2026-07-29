<?php

namespace App\Data;

final readonly class AttendanceDevicePullResult
{
    /**
     * @param  list<AttendanceEventData>  $events
     * @param  array<string, scalar|null>  $safeMetadata
     */
    public function __construct(
        public array $events,
        public ?string $nextCursor,
        public array $safeMetadata = [],
    ) {}
}
