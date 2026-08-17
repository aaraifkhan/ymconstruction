<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Employee profile')
                ->schema([
                    ImageEntry::make('photograph_path')
                        ->label('Photograph')
                        ->disk('public')
                        ->circular()
                        ->placeholder('No photograph uploaded'),
                    TextEntry::make('full_name')->label('Full name'),
                    IconEntry::make('is_active')->label('Active profile')->boolean(),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('Current company employment')
                ->schema([
                    TextEntry::make('current_employee_code')
                        ->label('Employee code')
                        ->badge()
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->employee_code ?? '—'),
                    TextEntry::make('current_department')
                        ->label('Department')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->department?->name ?? '—'),
                    TextEntry::make('current_designation')
                        ->label('Designation')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->designation?->name ?? '—'),
                    TextEntry::make('current_reporting_to')
                        ->label('Reporting to')
                        ->state(function (Employee $record): string {
                            $employment = self::activeEmployment($record);
                            $manager = $employment?->reportingEmployment;

                            return $manager !== null
                                ? "{$manager->employee->full_name} ({$manager->employee_code})"
                                : '—';
                        }),
                    TextEntry::make('current_joining_date')
                        ->label('Joining date')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->joining_date?->format('Y-m-d') ?? '—'),
                    TextEntry::make('current_status')
                        ->label('Status')
                        ->badge()
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->employment_status?->label() ?? '—'),
                    TextEntry::make('current_type')
                        ->label('Employment type')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->employment_type?->label() ?? '—'),
                    TextEntry::make('current_category')
                        ->label('Category')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->employment_category?->label() ?? '—'),
                    TextEntry::make('current_work_location')
                        ->label('Work location')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->workLocation?->name ?? '—'),
                    TextEntry::make('current_working_days')
                        ->label('Working days / week')
                        ->state(fn (Employee $record): string => (string) (self::activeEmployment($record)?->working_days_per_week ?? '—')),
                    TextEntry::make('current_probation')
                        ->label('Probation period')
                        ->state(function (Employee $record): string {
                            $employment = self::activeEmployment($record);
                            if ($employment === null || $employment->probation_start_date === null) {
                                return '—';
                            }

                            $start = $employment->probation_start_date->format('Y-m-d');
                            $end = $employment->probation_end_date?->format('Y-m-d') ?? 'Ongoing';

                            return "{$start} to {$end}";
                        }),
                    TextEntry::make('current_confirmation_date')
                        ->label('Confirmation date')
                        ->state(fn (Employee $record): ?string => self::activeEmployment($record)?->confirmation_date?->format('Y-m-d') ?? '—'),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('Identity information')
                ->schema([
                    TextEntry::make('father_or_husband_name')->label("Father's / husband's name")->placeholder('—'),
                    TextEntry::make('cnic'),
                    TextEntry::make('date_of_birth')->date()->placeholder('—'),
                    TextEntry::make('gender')->formatStateUsing(fn (?Gender $state): string => $state?->label() ?? '—'),
                    TextEntry::make('marital_status')->formatStateUsing(fn (?MaritalStatus $state): string => $state?->label() ?? '—'),
                    TextEntry::make('nationality')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewIdentity', $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Contact information')
                ->schema([
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('mobile')->placeholder('—'),
                    TextEntry::make('alternate_contact')->label('Alternate contact')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewContact', $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Medical information')
                ->schema([
                    TextEntry::make('blood_group')->label('Blood group')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewMedical', $record))
                ->columnSpanFull(),
            Section::make('Document compliance')
                ->schema([
                    TextEntry::make('hr_document_compliance')
                        ->label('Required documents')
                        ->state(function (Employee $record): string {
                            $company = Filament::getTenant();

                            if ($company === null) {
                                return 'Company unavailable';
                            }

                            $missing = $record->missingRequiredHrDocumentTypes($company)->pluck('name');

                            return $missing->isEmpty()
                                ? 'Complete — no required document is missing'
                                : 'Missing: '.$missing->join(', ');
                        }),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function activeEmployment(Employee $record): ?Employment
    {
        $companyId = Filament::getTenant()?->getKey();

        if ($companyId === null) {
            return null;
        }

        return $record->employments
            ->firstWhere('company_id', $companyId);
    }
}
