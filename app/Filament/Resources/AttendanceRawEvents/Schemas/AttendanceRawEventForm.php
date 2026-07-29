<?php

namespace App\Filament\Resources\AttendanceRawEvents\Schemas;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceRawEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('attendance_device_id')
                    ->relationship('attendanceDevice', 'name')
                    ->required(),
                TextInput::make('attendance_import_batch_id')
                    ->numeric(),
                TextInput::make('attendance_device_user_mapping_id')
                    ->numeric(),
                Select::make('employment_id')
                    ->relationship('employment', 'id'),
                TextInput::make('external_user_id')
                    ->required(),
                TextInput::make('original_punched_at_local')
                    ->required(),
                TextInput::make('timezone')
                    ->required(),
                DateTimePicker::make('punched_at_utc')
                    ->required(),
                Select::make('direction')
                    ->options(AttendancePunchDirection::class),
                TextInput::make('source_event_id'),
                Textarea::make('safe_payload')
                    ->columnSpanFull(),
                TextInput::make('event_fingerprint')
                    ->required(),
                Select::make('processing_status')
                    ->options(AttendanceRawEventStatus::class)
                    ->default('pending')
                    ->required(),
                Textarea::make('processing_error')
                    ->columnSpanFull(),
                DateTimePicker::make('received_at')
                    ->required(),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
