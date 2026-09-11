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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceRawEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Raw Attendance Event Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('attendance_device_id')
                            ->label('Attendance Device')
                            ->options(fn (): array => AttendanceDevice::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
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
                        TextInput::make('attendance_import_batch_id')->label('Batch ID')->numeric(),
                        TextInput::make('attendance_device_user_mapping_id')->label('Device User Mapping ID')->numeric(),
                        TextInput::make('external_user_id')->label('External Machine User ID')->required(),
                        TextInput::make('original_punched_at_local')->label('Local Punch Time')->required(),
                        TextInput::make('timezone')->label('Timezone')->required(),
                        DateTimePicker::make('punched_at_utc')->label('UTC Punch Time')->required(),
                        Select::make('direction')->label('Punch Direction')->options(AttendancePunchDirection::class),
                        TextInput::make('source_event_id')->label('Source Event ID'),
                        TextInput::make('event_fingerprint')->label('Event Fingerprint')->required(),
                        Select::make('processing_status')->label('Processing Status')->options(AttendanceRawEventStatus::class)->default('pending')->required(),
                        DateTimePicker::make('received_at')->label('Received At')->required(),
                        DateTimePicker::make('processed_at')->label('Processed At'),
                        Textarea::make('safe_payload')->label('Raw Ingest Payload')->rows(2)->columnSpanFull(),
                        Textarea::make('processing_error')->label('Processing Error')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
