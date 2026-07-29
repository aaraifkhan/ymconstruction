<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveLedgerEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('leaveType.name')
                    ->label('Leave type'),
                TextEntry::make('entry_type')
                    ->badge(),
                TextEntry::make('effective_on')
                    ->date(),
                TextEntry::make('units')
                    ->numeric(),
                TextEntry::make('source_type')
                    ->placeholder('-'),
                TextEntry::make('source_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
