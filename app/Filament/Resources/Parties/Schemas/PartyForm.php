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
                Section::make('Party Identity & Commercial Profile')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Party Code')
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
                            ->label('Trade / Display Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Registered Legal Name')
                            ->maxLength(255),
                        Select::make('roles')
                            ->label('Party Roles (Vendor / Customer / Consultant)')
                            ->options(PartyRole::class)
                            ->multiple()
                            ->required(),
                        TextInput::make('tax_number')
                            ->label('NTN / Tax Registration Number')
                            ->maxLength(100),
                        TextInput::make('payment_terms_days')
                            ->label('Credit Terms (Days)')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ]),
                Section::make('Contact Details & Registered Address')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Phone / Mobile')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('address')
                            ->label('Office / Billing Address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
