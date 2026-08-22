<?php

namespace App\Filament\Resources\CostCenters\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class CostCenterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cost Center Details')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Cost Center Code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(50)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                'company_id',
                                Filament::getTenant()?->getKey(),
                            ),
                        ),
                    TextInput::make('name')
                        ->label('Cost Center Name')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                    Textarea::make('description')
                        ->label('Description / Purpose')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
