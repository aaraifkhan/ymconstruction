<?php

namespace App\Filament\Resources\CompanyHolidays\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CompanyHolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CompanyContextField::make(),
                Select::make('work_calendar_id')
                    ->relationship('workCalendar', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
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
