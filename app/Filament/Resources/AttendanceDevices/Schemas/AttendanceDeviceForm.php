<?php

namespace App\Filament\Resources\AttendanceDevices\Schemas;

use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceDeviceTransport;
use App\Models\WorkLocation;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttendanceDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('work_location_id')
                    ->options(fn (): array => WorkLocation::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                TextInput::make('device_identifier')
                    ->required()
                    ->maxLength(191),
                Select::make('timezone')
                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                    ->searchable()
                    ->default('Asia/Karachi')
                    ->required(),
                Select::make('transport')
                    ->options(AttendanceDeviceTransport::class)
                    ->required(),
                TextInput::make('connection_profile_reference'),
                Select::make('health_status')
                    ->options(AttendanceDeviceHealthStatus::class)
                    ->default('unknown')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                DateTimePicker::make('last_sync_at')->disabled(),
                DateTimePicker::make('last_seen_at')->disabled(),
                Textarea::make('last_cursor')
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('last_error_summary')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
