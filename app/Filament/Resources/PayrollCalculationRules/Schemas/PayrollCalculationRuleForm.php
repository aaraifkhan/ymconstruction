<?php

namespace App\Filament\Resources\PayrollCalculationRules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PayrollCalculationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                DatePicker::make('effective_from')->required(),
                DatePicker::make('effective_to'),
                Toggle::make('requires_finalized_attendance')
                    ->helperText('When enabled, generation is blocked until every Employment has an exact finalized monthly summary.'),
                Toggle::make('prorate_allowances'),
                TextInput::make('absence_day_factor')->numeric()->minValue(0)->maxValue(1),
                TextInput::make('unpaid_leave_day_factor')->numeric()->minValue(0)->maxValue(1),
                TextInput::make('half_day_factor')->numeric()->minValue(0)->maxValue(1),
                Toggle::make('deduct_late_minutes'),
                TextInput::make('standard_day_minutes')->integer()->minValue(1)
                    ->helperText('Required only when late-minute deduction is enabled.'),
                Toggle::make('is_active')->default(true),
            ])->columns(2);
    }
}
