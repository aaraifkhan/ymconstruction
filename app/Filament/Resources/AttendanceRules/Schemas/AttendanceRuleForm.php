<?php

namespace App\Filament\Resources\AttendanceRules\Schemas;

use App\Enums\MissingPunchTreatment;
use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Attendance Shift & Grace Parameters')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        TextInput::make('name')
                            ->label('Rule Name')
                            ->required(),
                        DatePicker::make('effective_from')
                            ->label('Effective From')
                            ->required(),
                        DatePicker::make('effective_to')
                            ->label('Effective To'),
                        TextInput::make('grace_minutes')
                            ->label('Grace Minutes')
                            ->required()
                            ->numeric(),
                        TextInput::make('late_rounding_minutes')
                            ->label('Late Rounding (Minutes)')
                            ->required()
                            ->numeric(),
                        TextInput::make('half_day_after_minutes')
                            ->label('Half-Day After (Minutes)')
                            ->required()
                            ->numeric(),
                        TextInput::make('absence_after_minutes')
                            ->label('Full Absence After (Minutes)')
                            ->required()
                            ->numeric(),
                        TextInput::make('minimum_overtime_minutes')
                            ->label('Min Overtime Threshold (Minutes)')
                            ->required()
                            ->numeric(),
                        Select::make('missing_punch_treatment')
                            ->label('Missing Punch Treatment')
                            ->options(MissingPunchTreatment::class)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
