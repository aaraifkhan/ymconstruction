<?php

namespace App\Filament\Resources\WorkCalendars\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkCalendarForm
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
                TextInput::make('timezone')
                    ->required()
                    ->default('Asia/Karachi'),
                Textarea::make('working_weekdays')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('effective_from')
                    ->required(),
                DatePicker::make('effective_to'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
