<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Identity & Registration')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Company Display Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Registered Legal Name')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Tenant Slug / URL Key')
                            ->helperText('Used in URLs: e.g. bunyan-construction.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('registration_number')
                            ->label('SECP / Reg Number')
                            ->maxLength(255),
                        TextInput::make('tax_number')
                            ->label('NTN / Tax Number')
                            ->maxLength(255),
                    ]),
                Section::make('Contact Details & Registered Office')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email')
                            ->label('Corporate Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telephone / Hotline')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('website')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Registered Head Office Address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Localization & Operational Status')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('country_code')
                            ->label('Country Code')
                            ->default('PK')
                            ->required()
                            ->length(2),
                        TextInput::make('currency_code')
                            ->label('Base Currency')
                            ->default('PKR')
                            ->required()
                            ->length(3),
                        TextInput::make('timezone')
                            ->label('Timezone')
                            ->default('Asia/Karachi')
                            ->required()
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->label('Is Company Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
