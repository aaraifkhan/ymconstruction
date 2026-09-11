<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceImportRowErrorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Attendance Import Error Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        TextInput::make('attendance_import_batch_id')
                            ->label('Batch ID')
                            ->required()
                            ->numeric(),
                        TextInput::make('row_number')
                            ->label('Row Number')
                            ->required()
                            ->numeric(),
                        TextInput::make('error_code')
                            ->label('Error Code')
                            ->required(),
                        TextInput::make('external_reference')
                            ->label('External Reference'),
                        Textarea::make('message')
                            ->label('Error Message')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('safe_row_data')
                            ->label('Raw Row Snapshot')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
