<?php

namespace App\Filament\Resources\ShiftAssignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ShiftAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('employment_id')
                    ->relationship('employment', 'id')
                    ->required(),
                Select::make('work_calendar_id')
                    ->relationship('workCalendar', 'name')
                    ->required(),
                Select::make('work_shift_id')
                    ->relationship('workShift', 'name')
                    ->required(),
                DatePicker::make('effective_from')
                    ->required(),
                DatePicker::make('effective_to'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
