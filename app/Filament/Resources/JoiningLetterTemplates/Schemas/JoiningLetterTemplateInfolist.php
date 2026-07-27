<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JoiningLetterTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('code')->badge(),
                    TextEntry::make('subject_template')->label('Subject')->columnSpanFull(),
                    TextEntry::make('body_template')
                        ->label('Body')
                        ->prose()
                        ->columnSpanFull(),
                    IconEntry::make('is_default')->label('Default')->boolean(),
                    IconEntry::make('is_active')->label('Active')->boolean(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
