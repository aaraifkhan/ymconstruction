<?php

namespace App\Filament\Widgets;

use App\Actions\Tasks\ApproveTaskByHeadAction;
use App\Actions\Tasks\RequestTaskRevisionAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingApprovalsQueueWidget extends TableWidget
{
    protected static ?string $heading = '🚨 Tasks Awaiting Approval & Review';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        return $table
            ->query(
                Task::query()
                    ->where('company_id', $company?->id)
                    ->whereIn('status', [TaskStatus::Submitted, TaskStatus::Approved])
                    ->whereNull('completed_at')
                    ->with(['departmentTeam', 'assignee.employee'])
                    ->orderBy('deadline_date', 'asc')
            )
            ->columns([
                TextColumn::make('task_code')->badge()->label('ID'),
                TextColumn::make('title')->label('Task Title')->wrap(),
                TextColumn::make('departmentTeam.name')->label('Team')->placeholder('—'),
                TextColumn::make('assignee.employee.full_name')->label('Submitted By'),
                TextColumn::make('priority')->badge()->label('Priority')
                    ->color(fn (TaskPriority $state) => $state->color())
                    ->formatStateUsing(fn (TaskPriority $state) => $state->label()),
                TextColumn::make('submitted_at')->dateTime()->label('Submitted At'),
                TextColumn::make('deadline_date')->date()->label('Deadline'),
            ])
            ->recordActions([
                Action::make('approve_task')
                    ->label('Approve & Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->schema([
                        Textarea::make('notes')->label('Approval Remarks'),
                    ])
                    ->action(function (Task $record, array $data): void {
                        app(ApproveTaskByHeadAction::class)->handle($record, auth()->user(), $data['notes'] ?? null);
                        Notification::make()->title('Task Approved & Completed!')->success()->send();
                    }),

                Action::make('request_revision')
                    ->label('Request Revision')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->schema([
                        Textarea::make('revision_notes')->label('Revision Instructions')->required(),
                    ])
                    ->action(function (Task $record, array $data): void {
                        app(RequestTaskRevisionAction::class)->handle($record, auth()->user(), $data['revision_notes']);
                        Notification::make()->title('Revision Requested')->warning()->send();
                    }),
            ]);
    }
}
