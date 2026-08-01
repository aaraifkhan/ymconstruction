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
            ->components([
                Section::make('Company identity')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal name')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('URL key')
                            ->helperText('A permanent short key used in company URLs, for example: bunyan-construction.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('registration_number')
                            ->label('Registration number')
                            ->maxLength(255),
                        TextInput::make('tax_number')
                            ->label('Tax number / NTN')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Contact information')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('city')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Localization and status')
                    ->schema([
                        TextInput::make('country_code')
                            ->label('Country code')
                            ->default('PK')
                            ->required()
                            ->length(2),
                        TextInput::make('currency_code')
                            ->label('Currency code')
                            ->default('PKR')
                            ->required()
                            ->length(3),
                        TextInput::make('timezone')
                            ->default('Asia/Karachi')
                            ->required()
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
