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
                Section::make('Module configuration')
                    ->schema([
                        TextEntry::make('module.name')
                            ->label('Module'),
                        TextEntry::make('state')
                            ->badge()
                            ->formatStateUsing(fn (CompanyModuleState $state): string => $state->label()),
                        TextEntry::make('variant')
                            ->placeholder('Default workflow'),
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
