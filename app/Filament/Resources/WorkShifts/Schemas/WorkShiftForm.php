<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Work Shift Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        TextInput::make('code')
                            ->required(),
                        TextInput::make('name')
                            ->required(),
                        TimePicker::make('starts_at')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('ends_at')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('break_minutes')
                            ->label('Break (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_overnight')
                            ->required(),
                        Toggle::make('is_active')
                            ->required(),
                    ]),
            ]);
    }
}
