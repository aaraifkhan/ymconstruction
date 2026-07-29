<?php

namespace App\Filament\Resources\LeavePolicies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeavePolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('effective_from')
                    ->required(),
                DatePicker::make('effective_to'),
                TextInput::make('annual_units')
                    ->numeric(),
                TextInput::make('maximum_carry_forward_units')
                    ->numeric(),
                Toggle::make('allow_negative_balance')
                    ->required(),
                Toggle::make('allow_encashment')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
