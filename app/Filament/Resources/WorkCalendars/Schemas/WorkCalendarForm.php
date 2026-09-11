<?php

namespace App\Filament\Resources\WorkCalendars\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkCalendarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Work Calendar Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        TextInput::make('code')
                            ->required(),
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('timezone')
                            ->required()
                            ->default('Asia/Karachi'),
                        DatePicker::make('effective_from')
                            ->required(),
                        DatePicker::make('effective_to'),
                        Toggle::make('is_active')
                            ->required(),
                        Textarea::make('working_weekdays')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
