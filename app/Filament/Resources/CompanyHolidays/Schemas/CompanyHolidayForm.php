<?php

namespace App\Filament\Resources\CompanyHolidays\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CompanyHolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Holiday Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('work_calendar_id')
                            ->label('Work Calendar')
                            ->relationship('workCalendar', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        TextInput::make('name')
                            ->label('Holiday Name')
                            ->required(),
                        DatePicker::make('holiday_date')
                            ->label('Holiday Date')
                            ->required(),
                        Toggle::make('is_paid')
                            ->label('Paid Holiday')
                            ->default(true)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
