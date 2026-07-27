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
        return $schema->components([
            Section::make('Unit of measure')
                ->schema([
                    TextInput::make('code')
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
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('symbol')->required()->maxLength(20),
                    TextInput::make('decimal_places')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(4)
                        ->default(4),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
