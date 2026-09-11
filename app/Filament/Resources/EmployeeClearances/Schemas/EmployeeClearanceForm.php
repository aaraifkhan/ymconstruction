<?php

namespace App\Filament\Resources\EmployeeClearances\Schemas;

use Filament\Schemas\Schema;

class EmployeeClearanceForm
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
