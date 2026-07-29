<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Pages;

use App\Filament\Resources\AttendanceMonthlySummaries\AttendanceMonthlySummaryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceMonthlySummary extends CreateRecord
{
    protected static string $resource = AttendanceMonthlySummaryResource::class;
}
