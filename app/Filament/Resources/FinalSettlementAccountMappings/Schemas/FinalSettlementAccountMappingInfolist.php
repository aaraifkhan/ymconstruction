<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FinalSettlementAccountMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('component_type')->badge(),
                TextEntry::make('account.code'),
                TextEntry::make('account.name'),
                TextEntry::make('is_active')->badge(),
            ]);
    }
}
