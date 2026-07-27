<?php

namespace App\Filament\Resources\Designations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DesignationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Designation')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('code')->badge(),
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
