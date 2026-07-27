<?php

namespace App\Filament\Resources\FinancialPeriods\Schemas;

use App\Enums\FinancialPeriodStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FinancialPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('financial_year_id')->relationship('financialYear', 'name')->required(),
                TextInput::make('period_number')->numeric()->minValue(1)->maxValue(12)->required(),
                TextInput::make('name')->required()->maxLength(50),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required()->afterOrEqual('starts_on'),
                Select::make('status')->options(collect(FinancialPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))->disabled(),
                TextInput::make('reopen_reason')->disabled(),
            ]);
    }
}
