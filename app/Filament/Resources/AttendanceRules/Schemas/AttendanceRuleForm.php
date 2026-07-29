<?php

namespace App\Filament\Resources\AttendanceRules\Schemas;

use App\Enums\MissingPunchTreatment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttendanceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('effective_from')
                    ->required(),
                DatePicker::make('effective_to'),
                TextInput::make('grace_minutes')
                    ->required()
                    ->numeric(),
                TextInput::make('late_rounding_minutes')
                    ->required()
                    ->numeric(),
                TextInput::make('half_day_after_minutes')
                    ->required()
                    ->numeric(),
                TextInput::make('absence_after_minutes')
                    ->required()
                    ->numeric(),
                TextInput::make('minimum_overtime_minutes')
                    ->required()
                    ->numeric(),
                Select::make('missing_punch_treatment')
                    ->options(MissingPunchTreatment::class)
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
