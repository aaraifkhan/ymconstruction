<?php

namespace App\Filament\Resources\AttendanceCorrections\Schemas;

use App\Enums\AttendanceCorrectionStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\AttendanceRecord;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceCorrectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CompanyContextField::make(),
                Select::make('attendance_record_id')
                    ->label('Attendance record')
                    ->options(fn (): array => AttendanceRecord::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->with(['employment.employee'])
                        ->latest('attendance_date')
                        ->get()
                        ->mapWithKeys(fn (AttendanceRecord $record): array => [
                            $record->getKey() => "{$record->attendance_date?->toDateString()} — {$record->employment?->employee?->full_name} ({$record->employment?->employee_code})",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(AttendanceCorrectionStatus::class)
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                KeyValue::make('before_snapshot')
                    ->disabled()
                    ->columnSpanFull(),
                KeyValue::make('proposed_snapshot')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('requested_by_id')
                    ->relationship('requestedBy', 'name')
                    ->disabled(),
                Select::make('decided_by_id')
                    ->relationship('decidedBy', 'name')
                    ->disabled(),
                DateTimePicker::make('decided_at')->disabled(),
                Textarea::make('decision_reason')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
