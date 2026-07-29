<?php

namespace App\Filament\Resources\AttendanceImportBatches\Schemas;

use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceImportBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('attendance_device_id')
                    ->relationship('attendanceDevice', 'name'),
                Select::make('source')
                    ->options(AttendanceImportSource::class)
                    ->required(),
                Select::make('status')
                    ->options(AttendanceImportBatchStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('original_filename'),
                TextInput::make('stored_file_path'),
                TextInput::make('batch_checksum')
                    ->required(),
                Textarea::make('cursor_before')
                    ->columnSpanFull(),
                Textarea::make('cursor_after')
                    ->columnSpanFull(),
                Textarea::make('source_metadata')
                    ->columnSpanFull(),
                TextInput::make('row_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('accepted_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('duplicate_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('quarantined_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('error_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('initiated_by_id')
                    ->relationship('initiatedBy', 'name'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                Textarea::make('failure_summary')
                    ->columnSpanFull(),
            ]);
    }
}
