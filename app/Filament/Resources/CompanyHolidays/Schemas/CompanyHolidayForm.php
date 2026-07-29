<?php

namespace App\Filament\Resources\CompanyHolidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyHolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('work_calendar_id')
                    ->relationship('workCalendar', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('holiday_date')
                    ->required(),
                Toggle::make('is_paid')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
