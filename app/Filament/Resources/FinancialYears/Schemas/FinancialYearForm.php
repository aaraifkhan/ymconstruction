<?php

namespace App\Filament\Resources\FinancialYears\Schemas;

use App\Enums\FinancialPeriodStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FinancialYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(50),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required()->after('starts_on'),
                Select::make('status')->options(collect(FinancialPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))->required(),
            ]);
    }
}
