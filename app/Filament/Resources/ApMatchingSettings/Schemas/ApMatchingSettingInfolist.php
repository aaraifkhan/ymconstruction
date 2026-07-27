<?php

namespace App\Filament\Resources\ApMatchingSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApMatchingSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Three-way matching tolerances')->columns(4)->schema([
                TextEntry::make('quantity_tolerance_percentage')->suffix('%'),
                TextEntry::make('rate_tolerance_percentage')->suffix('%'),
                TextEntry::make('tax_tolerance_percentage')->suffix('%'),
                IconEntry::make('is_active')->boolean(),
            ]),
        ]);
    }
}
