<?php

namespace App\Filament\Resources\HrDocumentTypes\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrDocumentTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('HR document type')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('code')->badge(),
                    TextEntry::make('applicability')->badge(),
                    TextEntry::make('default_classification')->label('Default sensitivity')->badge(),
                    IconEntry::make('requires_issue_date')->boolean(),
                    IconEntry::make('requires_expiry')->boolean(),
                    IconEntry::make('requires_verification')->boolean(),
                    IconEntry::make('requires_approval')->boolean(),
                    IconEntry::make('is_required')->boolean(),
                    IconEntry::make('is_active')->boolean(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
