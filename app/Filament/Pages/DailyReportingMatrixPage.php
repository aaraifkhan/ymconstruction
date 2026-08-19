<?php

namespace App\Filament\Pages;

use App\Enums\DailyReportSubmissionStatus;
use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use App\Filament\Widgets\DailyReportingMatrixStatsWidget;
use App\Models\DepartmentTeam;
use App\Models\Employment;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyReportingMatrixPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?string $navigationLabel = 'Daily Attendance & Work Matrix';

    protected static ?string $title = 'Daily Attendance & Work Reporting Matrix';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.daily-reporting-matrix-page';

    public string $selectedDate = '';

    public ?int $selectedTeamId = null;

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
    }

    public function getHeaderWidgets(): array
    {
        return [
            DailyReportingMatrixStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previous_day')
                ->label('‹ Prev Day')
                ->color('gray')
                ->action(function (): void {
                    $this->selectedDate = CarbonImmutable::parse($this->selectedDate)->subDay()->toDateString();
                }),

            Action::make('set_today')
                ->label('Today')
                ->color('primary')
                ->action(function (): void {
                    $this->selectedDate = today()->toDateString();
                }),

            Action::make('next_day')
                ->label('Next Day ›')
                ->color('gray')
                ->action(function (): void {
                    $this->selectedDate = CarbonImmutable::parse($this->selectedDate)->addDay()->toDateString();
                }),

            Action::make('pick_date')
                ->label(fn (): string => '📅 Date: '.CarbonImmutable::parse($this->selectedDate)->format('M d, Y'))
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->schema([
                    DatePicker::make('date')
                        ->label('Select Reporting Date')
                        ->default(fn (): string => $this->selectedDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->selectedDate = $data['date'];
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        return $table
            ->query(
                Employment::query()
                    ->where('company_id', $company?->id)
                    ->where('employment_status', '!=', 'ended')
                    ->with([
                        'employee',
                        'department',
                        'designation',
                        'teamMemberships.team',
                        'dailyReports' => fn ($q) => $q->where('report_date', $this->selectedDate),
                    ])
            )
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Employment $record): string => "{$record->employee_code} • ".($record->designation?->name ?? '—')),

                TextColumn::make('team_name')
                    ->label('Team / Unit')
                    ->badge()
                    ->state(fn (Employment $record): string => $record->teamMemberships->where('is_active', true)->first()?->team?->name ?? 'General')
                    ->color('gray'),

                TextColumn::make('submission_status')
                    ->label('6:00 PM Status')
                    ->badge()
                    ->state(function (Employment $record): string {
                        $report = $record->dailyReports->first();
                        if (! $report) {
                            return '🔴 Missing';
                        }

                        return $report->submission_status === DailyReportSubmissionStatus::OnTime ? '🟢 On Time' : '🟠 Late';
                    })
                    ->color(function (Employment $record): string {
                        $report = $record->dailyReports->first();
                        if (! $report) {
                            return 'danger';
                        }

                        return $report->submission_status === DailyReportSubmissionStatus::OnTime ? 'success' : 'warning';
                    }),

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->state(fn (Employment $record): string => $record->dailyReports->first()?->submitted_at?->format('h:i A') ?? '—')
                    ->fontFamily('mono'),

                TextColumn::make('progress')
                    ->label('Day Progress')
                    ->state(fn (Employment $record): string => ($record->dailyReports->first()?->overall_progress_percentage ?? 0).'%')
                    ->color(function (Employment $record): string {
                        $progress = $record->dailyReports->first()?->overall_progress_percentage ?? 0;

                        return $progress >= 100 ? 'success' : ($progress >= 50 ? 'info' : 'gray');
                    }),

                TextColumn::make('deliverables_count')
                    ->label('Deliverables')
                    ->badge()
                    ->state(fn (Employment $record): int => $record->dailyReports->first()?->deliverables_count ?? 0)
                    ->color('primary'),

                TextColumn::make('blockers')
                    ->label('Active Blockers')
                    ->state(fn (Employment $record): string => $record->dailyReports->first()?->blockers_summary ?? 'None')
                    ->color(fn (Employment $record): ?string => $record->dailyReports->first()?->blockers_summary ? 'danger' : null)
                    ->limit(35)
                    ->tooltip(fn (Employment $record): ?string => $record->dailyReports->first()?->blockers_summary),
            ])
            ->filters([
                SelectFilter::make('department_team_id')
                    ->label('Filter by Team')
                    ->options(fn (): array => DepartmentTeam::query()
                        ->where('company_id', $company?->id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('teamMemberships', fn ($q) => $q->where('department_team_id', $data['value'])->where('is_active', true));
                    }),

                SelectFilter::make('reporting_status')
                    ->label('Submission Status')
                    ->options([
                        'on_time' => '🟢 On Time (<= 6 PM)',
                        'late' => '🟠 Late (> 6 PM)',
                        'missing' => '🔴 Missing / Not Submitted',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;
                        if (! $status) {
                            return $query;
                        }

                        if ($status === 'on_time') {
                            return $query->whereHas('dailyReports', fn ($q) => $q
                                ->where('report_date', $this->selectedDate)
                                ->where('submission_status', DailyReportSubmissionStatus::OnTime));
                        }

                        if ($status === 'late') {
                            return $query->whereHas('dailyReports', fn ($q) => $q
                                ->where('report_date', $this->selectedDate)
                                ->where('submission_status', DailyReportSubmissionStatus::Late));
                        }

                        if ($status === 'missing') {
                            return $query->whereDoesntHave('dailyReports', fn ($q) => $q->where('report_date', $this->selectedDate));
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                Action::make('view_report')
                    ->label('View Report')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(fn (Employment $record): bool => $record->dailyReports->first() !== null)
                    ->url(fn (Employment $record): ?string => $record->dailyReports->first()
                        ? DailyWorkReportResource::getUrl('view', ['record' => $record->dailyReports->first()->id, 'tenant' => $company->id])
                        : null),

                Action::make('remind_employee')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->visible(fn (Employment $record): bool => $record->dailyReports->first() === null)
                    ->action(function (Employment $record): void {
                        $user = $record->employee?->user;
                        if ($user) {
                            Notification::make()
                                ->title('⚠️ Daily Work Report Pending')
                                ->body('Please submit your daily work report for '.$this->selectedDate.' by the 6:00 PM cutoff.')
                                ->warning()
                                ->sendToDatabase($user);
                        }

                        Notification::make()
                            ->title('Reminder Sent')
                            ->body("Reminder notification sent to {$record->employee?->full_name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->poll('30s')
            ->defaultSort('employee_code', 'asc');
    }
}
