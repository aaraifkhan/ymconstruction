<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeCodeSequenceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Automatic employee-code format')
                ->schema([
                    TextEntry::make('prefix')->badge(),
                    TextEntry::make('padding'),
                    TextEntry::make('next_number')->label('Next number'),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
