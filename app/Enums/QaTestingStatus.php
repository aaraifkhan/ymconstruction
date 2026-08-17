<?php

namespace App\Enums;

enum QaTestingStatus: string
{
    case Untested = 'untested';
    case TestingInProgress = 'testing_in_progress';
    case Passed = 'passed';
    case FailedOrBugsFound = 'failed_bugs_found';

    public function label(): string
    {
        return match ($this) {
            self::Untested => 'Untested',
            self::TestingInProgress => 'Testing In Progress',
            self::Passed => 'QA Passed',
            self::FailedOrBugsFound => 'Bugs Found / QA Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Untested => 'gray',
            self::TestingInProgress => 'info',
            self::Passed => 'success',
            self::FailedOrBugsFound => 'danger',
        };
    }
}
