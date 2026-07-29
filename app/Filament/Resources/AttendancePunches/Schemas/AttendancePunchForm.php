<?php

namespace App\Filament\Resources\AttendancePunches\Schemas;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendancePunchForm
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
                DateTimePicker::make('punched_at')
                    ->required(),
                Select::make('direction')
                    ->options(AttendancePunchDirection::class)
                    ->required(),
                Select::make('status')
                    ->options(AttendancePunchStatus::class)
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('created_by_id')
                    ->relationship('createdBy', 'name')
                    ->disabled(),
                Select::make('approved_by_id')
                    ->relationship('approvedBy', 'name')
                    ->disabled(),
                DateTimePicker::make('approved_at')->disabled(),
                Textarea::make('rejection_reason')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
