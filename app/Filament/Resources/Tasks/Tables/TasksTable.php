<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Actions\Tasks\ApproveTaskByHeadAction;
use App\Actions\Tasks\RequestTaskRevisionAction;
use App\Actions\Tasks\ReviewTaskByLeadAction;
use App\Actions\Tasks\SubmitTaskAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
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

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task_code')->badge()->label('ID')->searchable()->sortable(),
                TextColumn::make('title')->label('Task Title')->searchable()->wrap(),
                TextColumn::make('departmentTeam.name')->label('Team')->sortable()->placeholder('General'),
                TextColumn::make('assignee.employee.full_name')->label('Assignee')->searchable(),
                TextColumn::make('priority')->badge()->label('Priority')
                    ->color(fn (TaskPriority $state) => $state->color())
                    ->formatStateUsing(fn (TaskPriority $state) => $state->label()),
                TextColumn::make('status')->badge()->label('Status')
                    ->color(fn (TaskStatus $state) => $state->color())
                    ->formatStateUsing(fn (TaskStatus $state) => $state->label()),
                TextColumn::make('progress_percentage')->label('Progress')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'info' : 'gray')),
                TextColumn::make('deadline_date')->date()->label('Deadline')->sortable()
                    ->color(fn (Task $record) => $record->status !== TaskStatus::Completed && $record->deadline_date && $record->deadline_date < now()->toDateString() ? 'danger' : null)
                    ->description(fn (Task $record) => $record->status !== TaskStatus::Completed && $record->deadline_date && $record->deadline_date < now()->toDateString() ? 'OVERDUE' : null),
                TextColumn::make('revision_count')->label('Revisions')->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('department_team_id')
                    ->relationship('departmentTeam', 'name')
                    ->label('Team'),
                SelectFilter::make('priority')
                    ->options(collect(TaskPriority::cases())->mapWithKeys(fn (TaskPriority $p) => [$p->value => $p->label()])),
                SelectFilter::make('status')
                    ->options(collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $s) => [$s->value => $s->label()])),
                Filter::make('overdue')
                    ->label('Overdue Tasks')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', '!=', TaskStatus::Completed->value)
                        ->whereNotNull('deadline_date')
                        ->where('deadline_date', '<', now()->toDateString())),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    // Submit Task (Assignee)
                    Action::make('submit_for_review')
                        ->label('Submit for Review')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn (Task $record) => in_array($record->status, [TaskStatus::NotStarted, TaskStatus::InProgress, TaskStatus::RevisionRequired], true))
                        ->schema([
                            Textarea::make('submission_note')
                                ->label('Submission Notes & Deliverable Links')
                                ->placeholder('Enter brief summary of work done or links...'),
                        ])
                        ->action(function (Task $record, array $data): void {
                            app(SubmitTaskAction::class)->handle($record, auth()->user(), $data['submission_note'] ?? null);
                            Notification::make()
                                ->title('Task Submitted for Review')
                                ->success()
                                ->send();
                        }),

                    // Team Lead Review (Level 1)
                    Action::make('lead_review')
                        ->label('Team Lead Review')
                        ->icon('heroicon-o-check-badge')
                        ->color('info')
                        ->visible(fn (Task $record) => $record->status === TaskStatus::Submitted && ! $record->lead_reviewed_at)
                        ->schema([
                            Textarea::make('notes')->label('Review Feedback / Notes'),
                        ])
                        ->action(function (Task $record, array $data): void {
                            app(ReviewTaskByLeadAction::class)->handle($record, auth()->user(), $data['notes'] ?? null);
                            Notification::make()
                                ->title('Task Reviewed by Team Lead')
                                ->info()
                                ->send();
                        }),

                    // Department Head Final Approval (Level 2)
                    Action::make('head_approval')
                        ->label('Head Final Approval')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Task $record) => in_array($record->status, [TaskStatus::Submitted, TaskStatus::Approved], true) && $record->status !== TaskStatus::Completed)
                        ->schema([
                            Textarea::make('notes')->label('Final Approval Remarks'),
                        ])
                        ->action(function (Task $record, array $data): void {
                            app(ApproveTaskByHeadAction::class)->handle($record, auth()->user(), $data['notes'] ?? null);
                            Notification::make()
                                ->title('Task Approved & Marked Completed!')
                                ->success()
                                ->send();
                        }),

                    // Request Revision (Lead or Head)
                    Action::make('request_revision')
                        ->label('Request Revision')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn (Task $record) => in_array($record->status, [TaskStatus::Submitted, TaskStatus::InProgress, TaskStatus::Approved], true))
                        ->schema([
                            Textarea::make('revision_notes')
                                ->label('Revision Instructions')
                                ->required()
                                ->placeholder('Explain what needs to be changed or corrected...'),
                        ])
                        ->action(function (Task $record, array $data): void {
                            app(RequestTaskRevisionAction::class)->handle($record, auth()->user(), $data['revision_notes']);
                            Notification::make()
                                ->title('Revision Requested')
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
