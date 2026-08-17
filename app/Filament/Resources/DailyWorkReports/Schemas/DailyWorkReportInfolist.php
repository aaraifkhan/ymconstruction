<?php

namespace App\Filament\Resources\DailyWorkReports\Schemas;

use App\Enums\DailyReportSubmissionStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DailyWorkReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Daily Report Overview')
                    ->schema([
                        TextEntry::make('employment.employee.full_name')->label('Employee Name'),
                        TextEntry::make('employment.employee_code')->badge()->label('Employee Code'),
                        TextEntry::make('department.name')->label('Department'),
                        TextEntry::make('departmentTeam.name')->label('Team')->placeholder('—'),
                        TextEntry::make('report_date')->date()->label('Reporting Date'),
                        TextEntry::make('submitted_at')->dateTime()->label('Submitted At'),
                        TextEntry::make('submission_status')->badge()->label('Status')
                            ->color(fn (DailyReportSubmissionStatus $state) => $state->color())
                            ->formatStateUsing(fn (DailyReportSubmissionStatus $state) => $state->label()),
                        TextEntry::make('overall_progress_percentage')->label('Overall Day Progress')
                            ->formatStateUsing(fn ($state) => "{$state}%"),
                        TextEntry::make('deliverables_count')->badge()->label('Deliverables Count'),
                        TextEntry::make('blockers_summary')->label('Blockers / Problems')->columnSpanFull()->placeholder('None reported.'),
                        TextEntry::make('additional_comments')->label('Additional Notes')->columnSpanFull()->placeholder('—'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Task Deliverables Breakdown')
                    ->schema([
                        RepeatableEntry::make('taskItems')
                            ->schema([
                                TextEntry::make('task_title')->label('Task / Work'),
                                TextEntry::make('status_today')->badge()->label('Status'),
                                TextEntry::make('hours_spent')->label('Hours Spent')->suffix(' hrs'),
                                TextEntry::make('progress_percentage')->label('Progress')->suffix('%'),
                                TextEntry::make('deliverable_summary')->label('Deliverable Summary')->placeholder('—'),
                                TextEntry::make('work_links')->label('Work Links')->placeholder('—'),
                                TextEntry::make('blockers')->label('Blocker')->placeholder('—'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
