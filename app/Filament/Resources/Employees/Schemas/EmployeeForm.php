<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Unique;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Employee profile')
                ->schema([
                    TextInput::make('full_name')->label('Full name')->required()->maxLength(255),
                    Toggle::make('is_active')->label('Active profile')->default(true)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Identity information')
                ->schema([
                    TextInput::make('father_or_husband_name')->label("Father's / husband's name")->maxLength(255),
                    TextInput::make('cnic')->label('CNIC')->placeholder('12345-1234567-1')->maxLength(15),
                    DatePicker::make('date_of_birth')->label('Date of birth')->beforeOrEqual('today'),
                    Select::make('gender')->options(collect(Gender::cases())->mapWithKeys(
                        fn (Gender $gender): array => [$gender->value => $gender->label()],
                    )->all()),
                    Select::make('marital_status')->label('Marital status')->options(collect(MaritalStatus::cases())->mapWithKeys(
                        fn (MaritalStatus $status): array => [$status->value => $status->label()],
                    )->all()),
                    TextInput::make('nationality')->default('Pakistani')->maxLength(100),
                ])
                ->visible(fn (string $operation, ?Employee $record): bool => self::canManageSensitive($operation, $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Contact and medical information')
                ->schema([
                    Textarea::make('address')->rows(3)->columnSpanFull(),
                    TextInput::make('mobile')->tel()->maxLength(50),
                    TextInput::make('alternate_contact')->label('Alternate contact')->tel()->maxLength(50),
                    TextInput::make('email')->email()->maxLength(255),
                    Select::make('blood_group')->label('Blood group')->options(array_combine(
                        ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                        ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                    )),
                ])
                ->visible(fn (string $operation, ?Employee $record): bool => self::canManageSensitive($operation, $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Initial company employment')
                ->description('Creates the profile and its first employment in the active company together.')
                ->schema([
                    TextInput::make('employment_employee_code')
                        ->label('Employee code')
                        ->required()
                        ->maxLength(100)
                        ->unique(
                            table: 'employments',
                            column: 'employee_code',
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                'company_id',
                                Filament::getTenant()?->getKey(),
                            ),
                        ),
                    DatePicker::make('employment_joining_date')->label('Date of joining')->required(),
                    Select::make('employment_department_id')
                        ->label('Department')
                        ->options(fn (): array => Filament::getTenant()?->departments()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all() ?? [])
                        ->searchable()
                        ->preload(),
                    Select::make('employment_designation_id')
                        ->label('Designation')
                        ->options(fn (): array => Filament::getTenant()?->designations()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all() ?? [])
                        ->searchable()
                        ->preload(),
                    Select::make('employment_reporting_to_employment_id')
                        ->label('Reporting to')
                        ->options(fn (): array => Employment::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->with('employee')
                            ->get()
                            ->mapWithKeys(fn (Employment $employment): array => [
                                $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('employment_employment_category')
                        ->label('Employee category')
                        ->options(collect(EmploymentCategory::cases())->mapWithKeys(
                            fn (EmploymentCategory $category): array => [$category->value => $category->label()],
                        )->all())
                        ->default(EmploymentCategory::AdministrativeStaff->value)
                        ->required(),
                    Select::make('employment_employment_status')
                        ->label('Status')
                        ->options(collect(EmploymentStatus::cases())->mapWithKeys(
                            fn (EmploymentStatus $status): array => [$status->value => $status->label()],
                        )->all())
                        ->default(EmploymentStatus::Probation->value)
                        ->required(),
                    TimePicker::make('employment_work_start_time')->label('Start time')->seconds(false),
                    TimePicker::make('employment_work_end_time')->label('End time')->seconds(false),
                    TextInput::make('employment_working_days_per_week')
                        ->label('Working days per week')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(7)
                        ->default(6)
                        ->required(),
                ])
                ->visibleOn('create')
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function canManageSensitive(string $operation, ?Employee $record): bool
    {
        if ($operation === 'create') {
            return auth()->user()?->can('ManageSensitive:Employee') ?? false;
        }

        return $record !== null && Gate::allows('manageSensitive', $record);
    }
}
