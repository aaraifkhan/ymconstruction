<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Enums\PartyRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Party details')
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
                        TextInput::make('legal_name')->maxLength(255),
                        Select::make('roles')
                            ->options(PartyRole::class)
                            ->multiple()
                            ->required(),
                        TextInput::make('tax_number')->maxLength(100),
                        TextInput::make('payment_terms_days')
                            ->label('Payment terms (days)')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('email')->email()->maxLength(255),
                        TextInput::make('phone')->tel()->maxLength(50),
                        Textarea::make('address')->rows(3)->columnSpanFull(),
                        Toggle::make('is_active')->label('Active')->default(true)->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
