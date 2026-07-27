<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OpeningBalanceMigrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('financialYear.name')
                    ->label('Financial year'),
                TextEntry::make('financialPeriod.name')
                    ->label('Financial period'),
                TextEntry::make('opening_date')
                    ->date(),
                TextEntry::make('idempotency_key'),
                TextEntry::make('source_filename'),
                TextEntry::make('source_checksum'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('row_count')
                    ->numeric(),
                TextEntry::make('valid_row_count')
                    ->numeric(),
                TextEntry::make('source_debit_total')
                    ->numeric(),
                TextEntry::make('source_credit_total')
                    ->numeric(),
                TextEntry::make('validation_summary')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('prepared_by_id')
                    ->numeric(),
                TextEntry::make('validated_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('validated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('imported_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('imported_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('openingBalanceBatch.id')
                    ->label('Opening balance batch')
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
