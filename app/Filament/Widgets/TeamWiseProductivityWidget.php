<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\DepartmentTeam;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TeamWiseProductivityWidget extends TableWidget
{
    protected static ?string $heading = 'Team-Wise Task & Performance Matrix';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        return $table
            ->query(
                DepartmentTeam::query()
                    ->where('company_id', $company?->id)
                    ->where('is_active', true)
                    ->with(['teamLead.employee', 'tasks'])
            )
            ->columns([
                TextColumn::make('name')->label('Team Name')->weight('bold'),
                TextColumn::make('team_type')->badge()->label('Type')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('teamLead.employee.full_name')->label('Team Lead')->placeholder('—'),
                TextColumn::make('assigned_count')
                    ->label('Assigned')
                    ->state(fn (DepartmentTeam $record) => $record->tasks()->count())
                    ->badge(),
                TextColumn::make('completed_count')
                    ->label('Completed')
                    ->state(fn (DepartmentTeam $record) => $record->tasks()->where('status', TaskStatus::Completed)->count())
                    ->badge()
                    ->color('success'),
                TextColumn::make('pending_count')
                    ->label('In Progress')
                    ->state(fn (DepartmentTeam $record) => $record->tasks()->whereIn('status', [TaskStatus::InProgress, TaskStatus::Submitted, TaskStatus::RevisionRequired])->count())
                    ->badge()
                    ->color('info'),
                TextColumn::make('overdue_count')
                    ->label('Overdue')
                    ->state(fn (DepartmentTeam $record) => $record->tasks()
                        ->where('status', '!=', TaskStatus::Completed)
                        ->whereNotNull('deadline_date')
                        ->where('deadline_date', '<', now()->toDateString())
                        ->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
            ]);
    }
}
