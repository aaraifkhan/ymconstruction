<?php

namespace App\Filament\Resources\PayrollAccountMappings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PayrollAccountMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('component')->badge(),
                TextEntry::make('account.code')->label('GL code'),
                TextEntry::make('account.name')->label('GL account'),
                IconEntry::make('is_active')->boolean(),
            ]);
    }
}
