<?php

namespace App\Filament\Resources\FinancialPeriods\Schemas;

use App\Enums\FinancialPeriodStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Financial Period Configuration')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('financial_year_id')
                            ->label('Financial Year')
                            ->relationship('financialYear', 'name')
                            ->required(),
                        TextInput::make('period_number')
                            ->label('Period Number (1-12)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required(),
                        TextInput::make('name')
                            ->label('Period Name / Code')
                            ->required()
                            ->maxLength(50),
                        DatePicker::make('starts_on')
                            ->label('Start Date')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->label('End Date')
                            ->required()
                            ->afterOrEqual('starts_on'),
                        Select::make('status')
                            ->label('Period Status')
                            ->options(collect(FinancialPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))
                            ->disabled(),
                        TextInput::make('reopen_reason')
                            ->label('Reopen Reason')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
