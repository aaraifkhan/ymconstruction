<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkLocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Work location')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('code')->badge(),
                    TextEntry::make('projectSite.name')->label('Project site')->placeholder('—'),
                    IconEntry::make('is_active')->boolean(),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
