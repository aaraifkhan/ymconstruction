<?php

namespace App\Filament\Resources\Employments\Schemas;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class EmploymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Employment Profile')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
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
                            ->label('Employee Code')
                            ->placeholder('Assigned automatically')
                            ->helperText('Company-specific code; existing codes remain unchanged.')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('joining_date')
                            ->label('Date of Joining')
                            ->minDate('2000-01-01')
                            ->maxDate(now()->addYear())
                            ->required(),
                        DatePicker::make('ending_date')
                            ->label('Ending Date')
                            ->afterOrEqual('joining_date'),
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship(
                                name: 'department',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('designation_id')
                            ->label('Designation')
                            ->options(function (Get $get): array {
                                $tenant = Filament::getTenant();

                                if ($tenant === null) {
                                    return [];
                                }

                                $departmentId = $get('department_id');

                                return $tenant->designations()
                                    ->where('is_active', true)
                                    ->when(
                                        filled($departmentId),
                                        fn (Builder $query): Builder => $query->where(
                                            fn (Builder $subQuery): Builder => $subQuery
                                                ->where('department_id', $departmentId)
                                                ->orWhereNull('department_id'),
                                        ),
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload(),
                        Select::make('reporting_to_employment_id')
                            ->label('Reporting To')
                            ->options(fn (?Employment $record): array => Employment::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->whereNotIn('employment_status', [
                                    EmploymentStatus::Resigned->value,
                                    EmploymentStatus::Terminated->value,
                                    EmploymentStatus::Ended->value,
                                ])
                                ->when($record !== null, fn (Builder $query): Builder => $query->whereKeyNot($record))
                                ->with('employee')
                                ->get()
                                ->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->placeholder('Select reporting manager (optional)'),
                        Select::make('employment_category')
                            ->label('Employee Category')
                            ->options(collect(EmploymentCategory::cases())->mapWithKeys(
                                fn (EmploymentCategory $category): array => [$category->value => $category->label()],
                            )->all())
                            ->default(EmploymentCategory::AdministrativeStaff->value)
                            ->required(),
                        Select::make('employment_type')
                            ->label('Employment Type')
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
                            ->label('Probation Start')
                            ->afterOrEqual('joining_date'),
                        DatePicker::make('probation_end_date')
                            ->label('Probation End')
                            ->afterOrEqual('probation_start_date'),
                        DatePicker::make('confirmation_date')
                            ->label('Confirmation Date')
                            ->afterOrEqual('joining_date'),
                        TextInput::make('notice_period_days')
                            ->label('Notice Period (Calendar Days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535),
                        Select::make('work_location_id')
                            ->label('Work Location')
                            ->relationship(
                                name: 'workLocation',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('cost_center_id')
                            ->label('Cost Center')
                            ->options(fn (): array => CostCenter::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Default cost center for payroll journal lines.'),
                        Select::make('default_project_id')
                            ->label('Default Project')
                            ->options(fn (): array => Project::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Applicable for project-staff employees.'),
                        Select::make('payment_method')
                            ->label('Disbursement Mode')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'cheque' => 'Cheque',
                            ])
                            ->default('bank_transfer')
                            ->required(),
                    ]),
                Section::make('Work Schedule & Working Hours')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TimePicker::make('work_start_time')->label('Work Start Time')->seconds(false),
                        TimePicker::make('work_end_time')->label('Work End Time')->seconds(false),
                        TextInput::make('working_days_per_week')
                            ->label('Working Days Per Week')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(7)
                            ->default(6)
                            ->required(),
                    ]),
                Section::make('HR Verification & Confidential Notes')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('interviewed_by_id')
                            ->label('Interview Conducted By')
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
                            ->label('Documents Verified By')
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
                            ->label('Appointment Letter Issued')
                            ->visible(fn (?Employment $record): bool => self::canManageHrVerification($record)),
                        Textarea::make('hr_notes')
                            ->label('Private HR Notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (?Employment $record): bool => $record === null
                                ? (auth()->user()?->can('ViewHrNotes:Employment') ?? false)
                                : Gate::allows('viewHrNotes', $record)),
                    ]),
            ]);
    }

    private static function canManageHrVerification(?Employment $record): bool
    {
        return $record === null
            ? (auth()->user()?->can('ManageHrVerification:Employment') ?? false)
            : Gate::allows('manageHrVerification', $record);
    }
}
