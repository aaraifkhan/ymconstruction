<?php

namespace App\Filament\Resources\FinalSettlements\Schemas;

use Filament\Schemas\Schema;

class FinalSettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                //
            ]);
    }
}
