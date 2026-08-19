<?php

namespace App\Filament\Widgets;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\Reactive;

class EmployeePerformanceTasksWidget extends TableWidget
{
    #[Reactive]
    public ?int $selectedEmploymentId = null;

    #[Reactive]
    public ?string $startDate = null;

    #[Reactive]
    public ?string $endDate = null;

    protected static ?string $heading = '📋 Tasks Assigned in Period';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();

        return $table
            ->query(
                Task::query()
                    ->where('company_id', $company?->id)
                    ->when($this->selectedEmploymentId, fn ($q) => $q->where('assigned_to_employment_id', $this->selectedEmploymentId))
                    ->when(! $this->selectedEmploymentId, fn ($q) => $q->whereRaw('1 = 0'))
                    ->whereBetween('created_at', [$start, $end])
                    ->with(['departmentTeam', 'assignee.employee'])
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('task_code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('title')
                    ->label('Task Title')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('departmentTeam.name')
                    ->label('Team')
                    ->badge()
                    ->color('info')
                    ->placeholder('General'),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (TaskPriority $state): string => $state->color())
                    ->formatStateUsing(fn (TaskPriority $state): string => $state->label()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (TaskStatus $state): string => $state->color())
                    ->formatStateUsing(fn (TaskStatus $state): string => $state->label()),

                TextColumn::make('revision_count')
                    ->label('Revisions')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),

                TextColumn::make('deadline_date')
                    ->label('Deadline')
                    ->date(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('Pending'),
            ])
            ->recordActions([
                Action::make('view_task')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record->id, 'tenant' => $company?->id])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
