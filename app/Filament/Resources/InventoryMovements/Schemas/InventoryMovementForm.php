<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Schemas\Schema;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([]);
    }
}
