<?php

namespace App\Filament\Resources\EmploymentCompensation\Schemas;

use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmploymentCompensationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Employment and Effective Period')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('employment_id')
                        ->label('Employee Employment')
                        ->options(fn (): array => Employment::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->with('employee')
                            ->get()
                            ->mapWithKeys(fn (Employment $employment): array => [
                                $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->length(3)
                        ->required(),
                    DatePicker::make('effective_from')
                        ->label('Effective From Date')
                        ->required(),
                    DatePicker::make('effective_to')
                        ->label('Effective To Date')
                        ->afterOrEqual('effective_from')
                        ->helperText('Leave empty while this compensation remains active.'),
                ]),
            Section::make('Monthly Compensation Breakdown')
                ->description('Gross salary is calculated dynamically from basic salary and all allowances.')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('basic_salary')
                        ->label('Basic Salary (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->required(),
                    TextInput::make('house_travel_allowance')
                        ->label('House & Travel Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('fuel_allowance')
                        ->label('Fuel Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('mobile_allowance')
                        ->label('Mobile Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('internet_allowance')
                        ->label('Internet Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('food_allowance')
                        ->label('Food Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('site_allowance')
                        ->label('Site Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('project_allowance')
                        ->label('Project Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('other_allowance')
                        ->label('Other Allowance (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0),
                    Textarea::make('notes')
                        ->label('Private Compensation Notes / Approval Memo')
                        ->maxLength(5000)
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
