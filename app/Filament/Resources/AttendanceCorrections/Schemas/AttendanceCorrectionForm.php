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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceCorrectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Attendance Correction Request Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('attendance_record_id')
                            ->label('Attendance Record')
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
                            ->required()
                            ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
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
                            ->rows(2)
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
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
