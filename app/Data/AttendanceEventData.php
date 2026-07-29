<?php

namespace App\Data;

use App\Enums\AttendanceImportSource;
use App\Enums\AttendancePunchDirection;

final readonly class AttendanceEventData
{
    /**
     * @param  array<string, scalar|null>  $safePayload
     */
    public function __construct(
        public string $deviceCode,
        public string $externalUserId,
        public string $punchedAtLocal,
        public string $timezone,
        public ?AttendancePunchDirection $direction,
        public ?string $sourceEventId,
        public AttendanceImportSource $source,
        public array $safePayload = [],
    ) {}
}
