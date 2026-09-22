<?php

namespace App\Filament\Resources\AttendanceDevices\Schemas;

use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceDeviceTransport;
use App\Filament\Support\CompanyContextField;
use App\Models\WorkLocation;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Biometric & Attendance Device Parameters')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('work_location_id')
                            ->label('Assigned Work Location')
                            ->options(fn (): array => WorkLocation::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable(),
                        TextInput::make('code')
                            ->label('Device Code')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('name')
                            ->label('Device Name')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('device_identifier')
                            ->label('Device Serial / IP Identifier')
                            ->required()
                            ->maxLength(191),
                        Select::make('timezone')
                            ->label('Timezone')
                            ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                            ->searchable()
                            ->default('Asia/Karachi')
                            ->required(),
                        Select::make('transport')
                            ->label('Communication Transport')
                            ->options(AttendanceDeviceTransport::class)
                            ->required(),
                        TextInput::make('connection_profile_reference')
                            ->label('Connection Profile Ref'),
                        Select::make('health_status')
                            ->label('Health Status')
                            ->options(AttendanceDeviceHealthStatus::class)
                            ->default('unknown')
                            ->disabled()
                            ->dehydrated(false),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        DateTimePicker::make('last_sync_at')->label('Last Sync Timestamp')->disabled(),
                        DateTimePicker::make('last_seen_at')->label('Last Seen Timestamp')->disabled(),
                        Textarea::make('last_cursor')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('last_error_summary')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
