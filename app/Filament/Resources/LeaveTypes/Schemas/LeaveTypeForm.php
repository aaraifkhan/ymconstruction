<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveUnit;
use App\Filament\Support\CompanyContextField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Leave Type Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
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
                    ]),
            ]);
    }
}
