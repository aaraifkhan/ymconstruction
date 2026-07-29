<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Schemas;

use App\Enums\AttendanceSummaryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceMonthlySummaryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('employment_id')
                    ->relationship('employment', 'id')
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
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('present_days')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('absent_days')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('half_days')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('leave_days')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('late_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('overtime_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('unpaid_leave_units')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('source_checksum')
                    ->required(),
                Select::make('finalized_by_id')
                    ->relationship('finalizedBy', 'name'),
                DateTimePicker::make('finalized_at'),
            ]);
    }
}
