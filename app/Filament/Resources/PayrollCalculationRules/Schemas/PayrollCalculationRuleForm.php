<?php

namespace App\Filament\Resources\PayrollCalculationRules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollCalculationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payroll Calculation & Attendance Deduction Rules')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Rule Name')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('effective_from')
                            ->label('Effective From')
                            ->required(),
                        DatePicker::make('effective_to')
                            ->label('Effective To'),
                        Toggle::make('requires_finalized_attendance')
                            ->label('Require Finalized Attendance')
                            ->helperText('When enabled, payroll generation is blocked until monthly summaries are finalized.'),
                        Toggle::make('prorate_allowances')
                            ->label('Prorate Allowances on Mid-Month Joining/Exit'),
                        TextInput::make('absence_day_factor')
                            ->label('Absence Day Deduction Factor (0.0 to 1.0)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1),
                        TextInput::make('unpaid_leave_day_factor')
                            ->label('Unpaid Leave Day Factor (0.0 to 1.0)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1),
                        TextInput::make('half_day_factor')
                            ->label('Half Day Factor (0.0 to 1.0)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1),
                        Toggle::make('deduct_late_minutes')
                            ->label('Deduct Late Minutes'),
                        TextInput::make('standard_day_minutes')
                            ->label('Standard Workday Minutes (e.g. 480 or 540)')
                            ->integer()
                            ->minValue(1)
                            ->helperText('Required only when late-minute deduction is enabled.'),
                        Toggle::make('is_active')
                            ->label('Is Rule Active')
                            ->default(true),
                    ]),
            ]);
    }
}
