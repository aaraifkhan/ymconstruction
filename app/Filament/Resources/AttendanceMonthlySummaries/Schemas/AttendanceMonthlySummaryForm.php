<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Schemas;

use App\Enums\AttendanceSummaryStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceMonthlySummaryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Attendance Monthly Summary Details')
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
                        DatePicker::make('period_start')
                            ->required(),
                        DatePicker::make('period_end')
                            ->required(),
                        Select::make('status')
                            ->options(AttendanceSummaryStatus::class)
                            ->default('draft')
                            ->required(),
                        TextInput::make('scheduled_days')
                            ->label('Scheduled Days')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('present_days')
                            ->label('Present Days')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('absent_days')
                            ->label('Absent Days')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('half_days')
                            ->label('Half Days')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('leave_days')
                            ->label('Leave Days')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('late_minutes')
                            ->label('Late (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('overtime_minutes')
                            ->label('Overtime (Minutes)')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('unpaid_leave_units')
                            ->label('Unpaid Leave Units')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('source_checksum')
                            ->label('Source Checksum')
                            ->required(),
                        Select::make('finalized_by_id')
                            ->label('Finalized By')
                            ->relationship('finalizedBy', 'name'),
                        DateTimePicker::make('finalized_at')
                            ->label('Finalized At'),
                    ]),
            ]);
    }
}
