<?php

namespace App\Filament\Resources\FinancialYears\Schemas;

use App\Enums\FinancialPeriodStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Financial Year Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Year Name / Code (e.g. FY-2026)')
                            ->required()
                            ->maxLength(50),
                        Select::make('status')
                            ->label('Year Status')
                            ->options(collect(FinancialPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))
                            ->required(),
                        DatePicker::make('starts_on')
                            ->label('Start Date')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->label('End Date')
                            ->required()
                            ->after('starts_on'),
                    ]),
            ]);
    }
}
