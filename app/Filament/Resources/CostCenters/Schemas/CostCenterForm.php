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
            Section::make('Cost center')
                ->schema([
                    TextInput::make('code')
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
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
