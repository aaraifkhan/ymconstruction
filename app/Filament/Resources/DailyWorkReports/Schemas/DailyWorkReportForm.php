<?php

namespace App\Filament\Resources\DailyWorkReports\Schemas;

use App\Enums\TeamType;
use App\Models\DepartmentTeam;
use App\Models\Employment;
use App\Models\Task;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DailyWorkReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Basic Information')
                    ->description('Daily work reporting is mandatory by 6:00 PM.')
                    ->schema([
                        Select::make('employment_id')
                            ->label('Employee')
                            ->relationship(
                                'employment',
                                'employee_code',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->getOptionLabelFromRecordUsing(fn (Employment $record) => "{$record->employee?->full_name} ({$record->employee_code}) - {$record->designation?->name}")
                            ->default(fn () => auth()->user()?->employee?->employments()->where('company_id', Filament::getTenant()?->id)->value('id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $emp = Employment::find($state);
                                    if ($emp) {
                                        $set('department_id', $emp->department_id);
                                        $activeTeam = $emp->teamMemberships()->where('is_active', true)->first();
                                        if ($activeTeam) {
                                            $set('department_team_id', $activeTeam->department_team_id);
                                        }
                                    }
                                }
                            })
                            ->required(),
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship(
                                'department',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('department_team_id')
                            ->label('Team / Unit')
                            ->relationship(
                                'departmentTeam',
                                'name',
                                fn (Builder $query, callable $get): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->when($get('department_id'), fn ($q, $deptId) => $q->where('department_id', $deptId))
                            )
                            ->searchable()
                            ->preload()
                            ->live(),
                        DatePicker::make('report_date')
                            ->label('Reporting Date')
                            ->default(now()->toDateString())
                            ->required(),
                        TextInput::make('overall_progress_percentage')
                            ->label('Overall Day Progress (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%'),
                        Textarea::make('blockers_summary')
                            ->label('Problems / Blockers Encountered')
                            ->rows(2)
                            ->placeholder('e.g. Waiting for client assets, API server down...')
                            ->columnSpan(2),
                        Textarea::make('additional_comments')
                            ->label('Additional Comments / Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull(),

                // Task Deliverables & Work Breakdown
                Section::make('Work & Tasks Completed Today')
                    ->schema([
                        Repeater::make('taskItems')
                            ->relationship('taskItems')
                            ->schema([
                                Select::make('task_id')
                                    ->label('Associated Assigned Task')
                                    ->relationship(
                                        'task',
                                        'title',
                                        fn (Builder $query, callable $get): Builder => $query
                                            ->whereBelongsTo(Filament::getTenant())
                                            ->when($get('../../employment_id'), fn ($q, $empId) => $q->where('assigned_to_employment_id', $empId))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $task = Task::find($state);
                                            if ($task) {
                                                $set('task_title', $task->title);
                                            }
                                        }
                                    })
                                    ->placeholder('Select task or enter custom title below'),
                                TextInput::make('task_title')
                                    ->label('Task / Work Summary')
                                    ->required()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('status_today')
                                    ->label('Status Today')
                                    ->options([
                                        'completed' => 'Completed Today',
                                        'in_progress' => 'In Progress',
                                        'pending' => 'Pending / On Hold',
                                    ])
                                    ->default('in_progress')
                                    ->required(),
                                TextInput::make('hours_spent')
                                    ->label('Hours Spent')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('hrs'),
                                TextInput::make('progress_percentage')
                                    ->label('Progress (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%'),
                                Textarea::make('deliverable_summary')
                                    ->label('Deliverable Details / Summary')
                                    ->rows(2)
                                    ->placeholder('e.g. Completed 2 social banners, coded header component...')
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                TextInput::make('work_links')
                                    ->label('Links to Work / Files')
                                    ->placeholder('https://drive.google.com/... or staging URL')
                                    ->columnSpanFull(),
                                Textarea::make('blockers')
                                    ->label('Task Specific Blockers')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                            ->columnSpanFull()
                            ->defaultItems(1),
                    ])
                    ->columnSpanFull(),

                // Specialized Sales Daily Outreach Section
                Section::make('💼 Sales & CRM Daily Outreach Metrics')
                    ->relationship('salesDetail')
                    ->visible(function (callable $get) {
                        $teamId = $get('department_team_id');
                        if (! $teamId) {
                            return false;
                        }
                        $team = DepartmentTeam::find($teamId);

                        return $team?->team_type === TeamType::Sales;
                    })
                    ->schema([
                        TextInput::make('leads_received_count')->label('Leads Received')->numeric()->default(0),
                        TextInput::make('leads_contacted_count')->label('Leads Contacted')->numeric()->default(0),
                        TextInput::make('calls_made_count')->label('Calls Made')->numeric()->default(0),
                        TextInput::make('messages_sent_count')->label('Messages Sent')->numeric()->default(0),
                        TextInput::make('followups_done_count')->label('Follow-ups Done')->numeric()->default(0),
                        TextInput::make('meetings_booked_count')->label('Meetings Booked')->numeric()->default(0),
                        TextInput::make('proposals_sent_count')->label('Proposals Sent')->numeric()->default(0),
                        TextInput::make('deals_closed_count')->label('Deals Closed')->numeric()->default(0),
                        TextInput::make('revenue_generated')->label('Revenue Generated (PKR)')->numeric()->prefix('PKR')->default(0),
                        TextInput::make('pending_leads_count')->label('Pending Leads')->numeric()->default(0),
                        TextInput::make('lost_leads_count')->label('Lost Leads')->numeric()->default(0),
                        Textarea::make('lost_lead_reasons')->label('Reason for Lost Leads')->rows(2)->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        Textarea::make('next_followup_targets')->label('Follow-up Targets for Tomorrow')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
