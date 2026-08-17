<?php

namespace App\Filament\Resources\DailyWorkReports\Tables;

use App\Enums\DailyReportReviewStatus;
use App\Enums\DailyReportSubmissionStatus;
use App\Models\DailyWorkReport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyWorkReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report_date')->date()->label('Date')->sortable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('employment.employee_code')->badge()->label('Emp Code')->searchable(),
                TextColumn::make('departmentTeam.name')->label('Team')->placeholder('—')->sortable(),
                TextColumn::make('submission_status')->badge()->label('Deadline Status')
                    ->color(fn (DailyReportSubmissionStatus $state) => $state->color())
                    ->formatStateUsing(fn (DailyReportSubmissionStatus $state) => $state->label()),
                TextColumn::make('submitted_at')->dateTime('h:i A')->label('Submitted At'),
                TextColumn::make('overall_progress_percentage')->label('Progress')
                    ->formatStateUsing(fn ($state) => "{$state}%"),
                TextColumn::make('deliverables_count')->badge()->label('Deliverables'),
                TextColumn::make('review_status')->badge()->label('Review')
                    ->color(fn (DailyReportReviewStatus $state) => $state->color())
                    ->formatStateUsing(fn (DailyReportReviewStatus $state) => $state->label()),
            ])
            ->filters([
                SelectFilter::make('department_team_id')
                    ->relationship('departmentTeam', 'name')
                    ->label('Team'),
                SelectFilter::make('submission_status')
                    ->options(collect(DailyReportSubmissionStatus::cases())->mapWithKeys(fn (DailyReportSubmissionStatus $s) => [$s->value => $s->label()]))
                    ->label('Deadline Status'),
                Filter::make('today')
                    ->label("Today's Reports")
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->where('report_date', now()->toDateString())),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    // Review Action (Team Lead / Head)
                    Action::make('review_report')
                        ->label('Review & Acknowledge')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (DailyWorkReport $record) => $record->review_status !== DailyReportReviewStatus::Acknowledged)
                        ->schema([
                            Textarea::make('review_notes')
                                ->label('Feedback / Acknowledgement Remarks')
                                ->placeholder('Great work today! or Please expedite banner revision...'),
                        ])
                        ->action(function (DailyWorkReport $record, array $data): void {
                            $record->update([
                                'review_status' => DailyReportReviewStatus::Acknowledged,
                                'reviewed_by_user_id' => auth()->id(),
                                'reviewed_at' => now(),
                                'review_notes' => $data['review_notes'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Daily Report Acknowledged')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('report_date', 'desc');
    }
}
