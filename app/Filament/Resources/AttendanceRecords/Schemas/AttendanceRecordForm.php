<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceRecordState;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CompanyContextField::make(),
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
                    ->searchable()
                    ->required(),
                Select::make('shift_assignment_id')
                    ->relationship('shiftAssignment', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                    ->disabled(),
                Select::make('attendance_rule_id')
                    ->relationship('attendanceRule', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                    ->disabled(),
                DatePicker::make('attendance_date')
                    ->required(),
                Select::make('day_status')
                    ->options(AttendanceDayStatus::class)
                    ->required(),
                Select::make('state')
                    ->options(AttendanceRecordState::class)
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                DateTimePicker::make('first_in_at')->disabled(),
                DateTimePicker::make('last_out_at')->disabled(),
                TextInput::make('scheduled_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                TextInput::make('worked_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                TextInput::make('late_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                TextInput::make('overtime_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                TextInput::make('source_checksum')->disabled(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Select::make('finalized_by_id')
                    ->relationship('finalizedBy', 'name')
                    ->disabled(),
                DateTimePicker::make('finalized_at')->disabled(),
            ]);
    }
}
