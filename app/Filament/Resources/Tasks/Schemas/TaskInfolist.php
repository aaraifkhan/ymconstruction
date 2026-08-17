<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task Overview')
                    ->schema([
                        TextEntry::make('task_code')->badge()->label('Task ID'),
                        TextEntry::make('title')->label('Task Title'),
                        TextEntry::make('department.name')->label('Department'),
                        TextEntry::make('departmentTeam.name')->label('Team')->placeholder('—'),
                        TextEntry::make('assignee.employee.full_name')->label('Assigned Employee'),
                        TextEntry::make('priority')->badge()->label('Priority')
                            ->color(fn ($state) => $state?->color() ?? 'gray')
                            ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                        TextEntry::make('status')->badge()->label('Status')
                            ->color(fn ($state) => $state?->color() ?? 'gray')
                            ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                        TextEntry::make('progress_percentage')->label('Progress')
                            ->formatStateUsing(fn ($state) => "{$state}%"),
                        TextEntry::make('start_date')->date()->label('Start Date')->placeholder('—'),
                        TextEntry::make('deadline_date')->date()->label('Deadline'),
                        TextEntry::make('revision_count')->badge()->label('Revisions Incurred'),
                        TextEntry::make('completed_at')->dateTime()->label('Completed At')->placeholder('Not completed'),
                        TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('blocker_note')->label('Active Blockers')->columnSpanFull()->placeholder('None reported.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Approval & Review Status')
                    ->schema([
                        TextEntry::make('leadReviewer.name')->label('Reviewed By (Lead)')->placeholder('Pending Review'),
                        TextEntry::make('lead_reviewed_at')->dateTime()->label('Lead Review Time')->placeholder('—'),
                        TextEntry::make('lead_review_notes')->label('Lead Notes')->placeholder('—'),
                        TextEntry::make('headApprover.name')->label('Approved By (Head)')->placeholder('Pending Approval'),
                        TextEntry::make('head_approved_at')->dateTime()->label('Head Approval Time')->placeholder('—'),
                        TextEntry::make('head_approval_notes')->label('Head Notes')->placeholder('—'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
