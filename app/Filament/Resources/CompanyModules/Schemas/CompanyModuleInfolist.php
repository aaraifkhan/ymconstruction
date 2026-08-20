<?php

namespace App\Filament\Resources\CompanyModules\Schemas;

use App\Enums\CompanyModuleState;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyModuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module details & capabilities')
                    ->schema([
                        TextEntry::make('module.name')
                            ->label('Module Name')
                            ->weight('bold'),
                        TextEntry::make('state')
                            ->badge()
                            ->formatStateUsing(fn (CompanyModuleState $state): string => $state->label())
                            ->color(fn (CompanyModuleState $state): string => match ($state) {
                                CompanyModuleState::Enabled => 'success',
                                CompanyModuleState::Disabled => 'danger',
                                CompanyModuleState::Inherit => 'gray',
                            }),
                        TextEntry::make('module.key')
                            ->label('Module Key')
                            ->fontFamily('mono'),
                        TextEntry::make('variant')
                            ->placeholder('Default workflow'),
                        TextEntry::make('module.description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('module.features')
                            ->label('Bundled Sub-Features & Capabilities')
                            ->bulleted()
                            ->columnSpanFull()
                            ->placeholder('No specific sub-features listed'),
                        KeyValueEntry::make('settings')
                            ->placeholder('No custom settings')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
