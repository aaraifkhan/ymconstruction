<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceImportRowErrorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('attendance_import_batch_id')
                    ->required()
                    ->numeric(),
                TextInput::make('row_number')
                    ->required()
                    ->numeric(),
                TextInput::make('error_code')
                    ->required(),
                TextInput::make('external_reference'),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('safe_row_data')
                    ->columnSpanFull(),
            ]);
    }
}
