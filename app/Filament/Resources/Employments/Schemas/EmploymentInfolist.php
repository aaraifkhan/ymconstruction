<?php

namespace App\Filament\Resources\Employments\Schemas;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Models\Employment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class EmploymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Employment')
                ->schema([
                    TextEntry::make('employee.full_name')->label('Employee'),
                    TextEntry::make('employee_code')->label('Employee code')->badge(),
                    TextEntry::make('designation.name')->placeholder('Not assigned'),
                    TextEntry::make('department.name')->placeholder('Not assigned'),
                    TextEntry::make('reportingEmployment.employee.full_name')->label('Reporting to')->placeholder('Not assigned'),
                    TextEntry::make('employment_category')
                        ->formatStateUsing(fn (EmploymentCategory $state): string => $state->label()),
                    TextEntry::make('employment_status')
                        ->label('Status')
                        ->formatStateUsing(fn (EmploymentStatus $state): string => $state->label())
                        ->badge(),
                    TextEntry::make('joining_date')->date(),
                    TextEntry::make('ending_date')->date()->placeholder('Current'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Work schedule')
                ->schema([
                    TextEntry::make('work_start_time')->label('Start time')->placeholder('Not set'),
                    TextEntry::make('work_end_time')->label('End time')->placeholder('Not set'),
                    TextEntry::make('working_days_per_week')->label('Days per week'),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('HR office use')
                ->schema([
                    TextEntry::make('interviewedBy.name')->label('Interview conducted by')->placeholder('Not recorded'),
                    TextEntry::make('documentsVerifiedBy.name')->label('Documents verified by')->placeholder('Not recorded'),
                    TextEntry::make('documents_verified_at')->dateTime()->placeholder('Not verified'),
                    IconEntry::make('appointment_letter_issued')->boolean(),
                    TextEntry::make('hr_notes')->label('Private HR notes')->placeholder('—')->columnSpanFull(),
                ])
                ->visible(fn (Employment $record): bool => Gate::allows('viewHrNotes', $record))
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
