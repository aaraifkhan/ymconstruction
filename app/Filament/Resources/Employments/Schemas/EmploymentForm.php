<?php

namespace App\Filament\Resources\Employments\Schemas;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class EmploymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company employment')
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(function (?Employment $record): array {
                            $companyId = Filament::getTenant()?->getKey();

                            return Employee::query()
                                ->where('is_active', true)
                                ->when(
                                    $companyId !== null,
                                    fn (Builder $query): Builder => $query->whereDoesntHave(
                                        'employments',
                                        fn (Builder $employmentQuery): Builder => $employmentQuery
                                            ->where('company_id', $companyId)
                                            ->when(
                                                $record !== null,
                                                fn (Builder $recordQuery): Builder => $recordQuery->whereKeyNot($record),
                                            ),
                                    ),
                                )
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabledOn('edit'),
                    TextInput::make('employee_code')
                        ->label('Employee code')
                        ->placeholder('Assigned automatically')
                        ->helperText('Company-specific code; existing codes remain unchanged.')
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('joining_date')
                        ->label('Date of joining')
                        ->required(),
                    DatePicker::make('ending_date')
                        ->label('Ending date')
                        ->afterOrEqual('joining_date'),
                    Select::make('department_id')
                        ->relationship(
                            name: 'department',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                    Select::make('designation_id')
                        ->relationship(
                            name: 'designation',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                    Select::make('reporting_to_employment_id')
                        ->label('Reporting to')
                        ->options(fn (?Employment $record): array => Employment::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->when($record !== null, fn (Builder $query): Builder => $query->whereKeyNot($record))
                            ->with('employee')
                            ->get()
                            ->mapWithKeys(fn (Employment $employment): array => [
                                $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('employment_category')
                        ->label('Employee category')
                        ->options(collect(EmploymentCategory::cases())->mapWithKeys(
                            fn (EmploymentCategory $category): array => [$category->value => $category->label()],
                        )->all())
                        ->default(EmploymentCategory::AdministrativeStaff->value)
                        ->required(),
                    Select::make('employment_type')
                        ->label('Employment type')
                        ->options(collect(EmploymentType::cases())->mapWithKeys(
                            fn (EmploymentType $type): array => [$type->value => $type->label()],
                        )->all())
                        ->default(EmploymentType::Permanent->value)
                        ->required(),
                    Select::make('employment_status')
                        ->label('Status')
                        ->options(fn (?Employment $record): array => collect(EmploymentStatus::cases())
                            ->reject(fn (EmploymentStatus $status): bool => $status->isLegacy()
                                && $record?->employment_status !== EmploymentStatus::Ended)
                            ->mapWithKeys(
                                fn (EmploymentStatus $status): array => [$status->value => $status->label()],
                            )->all())
                        ->default(EmploymentStatus::Probation->value)
                        ->required(),
                    DatePicker::make('probation_start_date')
                        ->label('Probation start')
                        ->afterOrEqual('joining_date'),
                    DatePicker::make('probation_end_date')
                        ->label('Probation end')
                        ->afterOrEqual('probation_start_date'),
                    DatePicker::make('confirmation_date')
                        ->label('Confirmation date')
                        ->afterOrEqual('joining_date'),
                    TextInput::make('notice_period_days')
                        ->label('Notice period (calendar days)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535),
                    Select::make('work_location_id')
                        ->label('Work location')
                        ->relationship(
                            name: 'workLocation',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Work schedule')
                ->schema([
                    TimePicker::make('work_start_time')->label('Start time')->seconds(false),
                    TimePicker::make('work_end_time')->label('End time')->seconds(false),
                    TextInput::make('working_days_per_week')
                        ->label('Working days per week')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(7)
                        ->default(6)
                        ->required(),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('HR office use')
                ->schema([
                    Select::make('interviewed_by_id')
                        ->label('Interview conducted by')
                        ->relationship(
                            name: 'interviewedBy',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->whereHas(
                                'companies',
                                fn (Builder $companyQuery): Builder => $companyQuery
                                    ->whereKey(Filament::getTenant())
                                    ->where('company_user.is_active', true),
                            ),
                        )
                        ->searchable()
                        ->preload()
                        ->visible(fn (?Employment $record): bool => self::canManageHrVerification($record)),
                    Select::make('documents_verified_by_id')
                        ->label('Documents verified by')
                        ->relationship(
                            name: 'documentsVerifiedBy',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->whereHas(
                                'companies',
                                fn (Builder $companyQuery): Builder => $companyQuery
                                    ->whereKey(Filament::getTenant())
                                    ->where('company_user.is_active', true),
                            ),
                        )
                        ->searchable()
                        ->preload()
                        ->visible(fn (?Employment $record): bool => self::canManageHrVerification($record)),
                    Toggle::make('appointment_letter_issued')
                        ->label('Appointment letter issued')
                        ->visible(fn (?Employment $record): bool => self::canManageHrVerification($record)),
                    Textarea::make('hr_notes')
                        ->label('Private HR notes')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn (?Employment $record): bool => $record === null
                            ? (auth()->user()?->can('ViewHrNotes:Employment') ?? false)
                            : Gate::allows('viewHrNotes', $record)),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function canManageHrVerification(?Employment $record): bool
    {
        return $record === null
            ? (auth()->user()?->can('ManageHrVerification:Employment') ?? false)
            : Gate::allows('manageHrVerification', $record);
    }
}
