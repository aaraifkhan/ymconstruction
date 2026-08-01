<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company identity')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('legal_name')
                            ->label('Legal name')
                            ->placeholder('Not provided'),
                        TextEntry::make('slug')
                            ->label('URL key')
                            ->copyable(),
                        TextEntry::make('registration_number')
                            ->label('Registration number')
                            ->placeholder('Not provided'),
                        TextEntry::make('tax_number')
                            ->label('Tax number / NTN')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Contact and localization')
                    ->schema([
                        TextEntry::make('email')
                            ->placeholder('Not provided'),
                        TextEntry::make('phone')
                            ->placeholder('Not provided'),
                        TextEntry::make('website')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('Not provided'),
                        TextEntry::make('city')
                            ->placeholder('Not provided'),
                        TextEntry::make('address')
                            ->placeholder('Not provided')
                            ->columnSpanFull(),
                        TextEntry::make('country_code')
                            ->label('Country'),
                        TextEntry::make('currency_code')
                            ->label('Currency'),
                        TextEntry::make('timezone'),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('System information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('Not archived'),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
