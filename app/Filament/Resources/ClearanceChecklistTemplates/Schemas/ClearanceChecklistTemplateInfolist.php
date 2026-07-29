<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClearanceChecklistTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('area')->badge(),
                TextEntry::make('description')->columnSpanFull(),
                TextEntry::make('is_mandatory')->boolean(),
                TextEntry::make('is_active')->boolean(),
                TextEntry::make('sort_order'),
            ]);
    }
}
