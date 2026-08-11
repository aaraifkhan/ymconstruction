<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CompanyContextField::make(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TimePicker::make('starts_at')
                    ->required(),
                TimePicker::make('ends_at')
                    ->required(),
                TextInput::make('break_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_overnight')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
