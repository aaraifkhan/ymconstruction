<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('unit')
                    ->options(LeaveUnit::class)
                    ->required(),
                Toggle::make('is_paid')
                    ->required(),
                Select::make('payroll_impact')
                    ->options(LeavePayrollImpact::class)
                    ->default('none')
                    ->required(),
                Toggle::make('requires_attachment')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
