<?php

namespace App\Filament\Resources\DailyWorkReports\Pages;

use App\Enums\DailyReportSubmissionStatus;
use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyWorkReport extends CreateRecord
{
    protected static string $resource = DailyWorkReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = now();
        $reportDate = Carbon::parse($data['report_date'] ?? now()->toDateString());
        $deadlineTime = $reportDate->copy()->setTime(18, 0, 0);

        $data['submitted_at'] = $now;
        $data['submission_status'] = $now->greaterThan($deadlineTime)
            ? DailyReportSubmissionStatus::Late->value
            : DailyReportSubmissionStatus::OnTime->value;

        return $data;
    }
}
