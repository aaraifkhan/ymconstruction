<?php

namespace App\Filament\Widgets;

use App\Enums\DailyReportSubmissionStatus;
use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use App\Models\DailyWorkReport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\Reactive;

class EmployeePerformanceReportsWidget extends TableWidget
{
    #[Reactive]
    public ?int $selectedEmploymentId = null;

    #[Reactive]
    public ?string $startDate = null;

    #[Reactive]
    public ?string $endDate = null;

    protected static ?string $heading = '📅 Daily Work Reports Submitted in Period';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();

        return $table
            ->query(
                DailyWorkReport::query()
                    ->where('company_id', $company?->id)
                    ->when($this->selectedEmploymentId, fn ($q) => $q->where('employment_id', $this->selectedEmploymentId))
                    ->when(! $this->selectedEmploymentId, fn ($q) => $q->whereRaw('1 = 0'))
                    ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
                    ->latest('report_date')
            )
            ->columns([
                TextColumn::make('report_date')
                    ->label('Report Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('submission_status')
                    ->label('6:00 PM Status')
                    ->badge()
                    ->state(fn (DailyWorkReport $record): string => $record->submission_status === DailyReportSubmissionStatus::OnTime ? '🟢 On Time' : '🟠 Late')
                    ->color(fn (DailyWorkReport $record): string => $record->submission_status === DailyReportSubmissionStatus::OnTime ? 'success' : 'warning'),

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('h:i A')
                    ->fontFamily('mono'),

                TextColumn::make('overall_progress_percentage')
                    ->label('Progress')
                    ->state(fn (DailyWorkReport $record): string => "{$record->overall_progress_percentage}%")
                    ->color(fn (DailyWorkReport $record): string => $record->overall_progress_percentage >= 100 ? 'success' : ($record->overall_progress_percentage >= 50 ? 'info' : 'gray')),

                TextColumn::make('deliverables_count')
                    ->label('Deliverables')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('tasks_completed_count')
                    ->label('Tasks Done')
                    ->badge()
                    ->color('success'),

                TextColumn::make('blockers_summary')
                    ->label('Blockers')
                    ->limit(35)
                    ->tooltip(fn (DailyWorkReport $record): ?string => $record->blockers_summary)
                    ->color(fn (DailyWorkReport $record): ?string => $record->blockers_summary ? 'danger' : null)
                    ->placeholder('None'),
            ])
            ->recordActions([
                Action::make('view_report')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (DailyWorkReport $record): string => DailyWorkReportResource::getUrl('view', ['record' => $record->id, 'tenant' => $company?->id])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
