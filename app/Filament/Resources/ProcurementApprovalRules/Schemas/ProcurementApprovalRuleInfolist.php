<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProcurementApprovalRuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('document_type')
                    ->badge(),
                TextEntry::make('step_number')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('minimum_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('maximum_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('permission_name'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
