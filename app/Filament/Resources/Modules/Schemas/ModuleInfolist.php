<?php

namespace App\Filament\Resources\Modules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ModuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('key')
                            ->copyable(),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
                        TextEntry::make('sort_order')
                            ->label('Display order'),
                        IconEntry::make('is_active')
                            ->label('Available for companies')
                            ->boolean(),
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
