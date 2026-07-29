<?php

namespace App\Filament\Resources\PayrollVariableComponents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PayrollVariableComponentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('source_reference'),
                TextEntry::make('employment.employee.full_name')->label('Employee'),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('earning_period_start')->date(),
                TextEntry::make('earning_period_end')->date(),
                TextEntry::make('amount')->money('PKR'),
                TextEntry::make('project.name')->placeholder('-'),
                TextEntry::make('source_checksum')->copyable()->columnSpanFull(),
            ])->columns(2);
    }
}
