<?php

namespace App\Filament\Resources\AttendanceRawEvents\Schemas;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\AttendanceDevice;
use App\Models\Employment;
use Filament\Facades\Filament;
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
                CompanyContextField::make(),
                Select::make('attendance_device_id')
                    ->options(fn (): array => AttendanceDevice::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('attendance_import_batch_id')
                    ->numeric(),
                TextInput::make('attendance_device_user_mapping_id')
                    ->numeric(),
                Select::make('employment_id')
                    ->label('Employment')
                    ->options(fn (): array => Employment::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->with('employee')
                        ->get()
                        ->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee_code} — {$employment->employee?->full_name}",
                        ])
                        ->all())
                    ->searchable(),
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
