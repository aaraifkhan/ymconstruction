<?php

namespace App\Filament\Resources\UnitOfMeasures\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class UnitOfMeasureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Unit of Measure (UOM) Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('UOM Code')
                            ->required()
                            ->alphaDash()
                            ->maxLength(30)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        TextInput::make('name')
                            ->label('UOM Name (e.g. Bag, Ton, Meter)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label('Symbol (e.g. kg, ft, m3)')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('decimal_places')
                            ->label('Precision (Decimal Places)')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(4)
                            ->default(4),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
