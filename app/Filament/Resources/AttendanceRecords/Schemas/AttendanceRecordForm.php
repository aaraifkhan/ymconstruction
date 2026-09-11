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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Daily Attendance Record Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
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
                        DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->required(),
                        Select::make('day_status')
                            ->label('Day Status')
                            ->options(AttendanceDayStatus::class)
                            ->required(),
                        Select::make('state')
                            ->label('Record State')
                            ->options(AttendanceRecordState::class)
                            ->default('draft')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('shift_assignment_id')
                            ->label('Shift Assignment')
                            ->relationship('shiftAssignment', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->disabled(),
                        Select::make('attendance_rule_id')
                            ->label('Applied Attendance Rule')
                            ->relationship('attendanceRule', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->disabled(),
                        DateTimePicker::make('first_in_at')->label('First In')->disabled(),
                        DateTimePicker::make('last_out_at')->label('Last Out')->disabled(),
                        TextInput::make('scheduled_minutes')
                            ->label('Scheduled (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('worked_minutes')
                            ->label('Worked (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('late_minutes')
                            ->label('Late (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('overtime_minutes')
                            ->label('Overtime (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('source_checksum')->label('Source Checksum')->disabled(),
                        Select::make('finalized_by_id')
                            ->label('Finalized By')
                            ->relationship('finalizedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('finalized_at')->label('Finalized At')->disabled(),
                        Textarea::make('notes')
                            ->label('Attendance Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
