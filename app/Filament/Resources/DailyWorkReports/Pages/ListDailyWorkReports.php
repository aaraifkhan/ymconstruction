<?php

namespace App\Filament\Resources\DailyWorkReports\Pages;

use App\Enums\DailyReportReviewStatus;
use App\Enums\DailyReportSubmissionStatus;
use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDailyWorkReports extends ListRecords
{
    protected static string $resource = DailyWorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Submit Daily Report'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reports'),
            'today' => Tab::make("Today's Submissions")
                ->modifyQueryUsing(fn (Builder $query) => $query->where('report_date', now()->toDateString())),
            'on_time' => Tab::make('🟢 On Time (<= 6 PM)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('submission_status', DailyReportSubmissionStatus::OnTime)),
            'late' => Tab::make('🟠 Late (> 6 PM)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('submission_status', DailyReportSubmissionStatus::Late)),
            'pending_review' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('review_status', DailyReportReviewStatus::Pending)),
        ];
    }
}
