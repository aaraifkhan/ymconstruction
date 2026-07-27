<?php

namespace App\Filament\Resources\YearEndClosings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class YearEndClosingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('financialYear.name')
                    ->label('Financial year'),
                TextEntry::make('idempotency_key'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('profit_or_loss')
                    ->numeric(),
                TextEntry::make('calculation_checksum')
                    ->placeholder('-'),
                TextEntry::make('calculation_snapshot')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('retainedEarningsAccount.name')
                    ->label('Retained earnings account'),
                TextEntry::make('prepared_by_id')
                    ->numeric(),
                TextEntry::make('approved_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('posted_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('journalEntry.id')
                    ->label('Journal entry')
                    ->placeholder('-'),
                TextEntry::make('reversed_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reversed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reversal_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('reversalEntry.id')
                    ->label('Reversal entry')
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
