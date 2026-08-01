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
            Section::make('Employment and effective period')
                ->schema([
                    Select::make('employment_id')
                        ->label('Employee employment')
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
                        ->required(),
                    DatePicker::make('effective_from')->required(),
                    DatePicker::make('effective_to')
                        ->afterOrEqual('effective_from')
                        ->helperText('Leave empty while this compensation remains active.'),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->length(3)
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Monthly compensation')
                ->description('Gross salary is calculated from basic salary and all allowances.')
                ->schema([
                    TextInput::make('basic_salary')
                        ->label('Basic salary')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('house_travel_allowance')
                        ->label('House & travel allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('fuel_allowance')
                        ->label('Fuel allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('mobile_allowance')
                        ->label('Mobile allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('internet_allowance')
                        ->label('Internet allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('food_allowance')
                        ->label('Food allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('site_allowance')
                        ->label('Site allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('project_allowance')
                        ->label('Project allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('other_allowance')
                        ->label('Other allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Textarea::make('notes')
                        ->label('Private compensation notes')
                        ->maxLength(5000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
