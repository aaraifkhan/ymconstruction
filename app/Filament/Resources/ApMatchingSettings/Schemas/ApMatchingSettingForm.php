<?php

namespace App\Filament\Resources\ApMatchingSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApMatchingSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Three-Way Matching Tolerances')
                    ->description('Defaults are zero. These tolerances never permit billing beyond handed-over accepted quantity.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('quantity_tolerance_percentage')
                            ->label('Quantity Tolerance (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        TextInput::make('rate_tolerance_percentage')
                            ->label('Rate Tolerance (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        TextInput::make('tax_tolerance_percentage')
                            ->label('Tax Tolerance (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true),
                    ]),
            ]);
    }
}
