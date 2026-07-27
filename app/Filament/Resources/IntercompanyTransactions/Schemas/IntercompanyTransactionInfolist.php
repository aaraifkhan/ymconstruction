<?php

namespace App\Filament\Resources\IntercompanyTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class IntercompanyTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('counterpartyCompany.name')
                    ->label('Counterparty company'),
                TextEntry::make('idempotency_key'),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('direction')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('originOffsetAccount.name')
                    ->label('Origin offset account'),
                TextEntry::make('counterpartyOffsetAccount.name')
                    ->label('Counterparty offset account'),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('prepared_by_id')
                    ->numeric(),
                TextEntry::make('origin_approved_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('origin_approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('counterparty_approved_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('counterparty_approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejected_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rejected_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejection_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('posted_by_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('originJournalEntry.id')
                    ->label('Origin journal entry')
                    ->placeholder('-'),
                TextEntry::make('counterpartyJournalEntry.id')
                    ->label('Counterparty journal entry')
                    ->placeholder('-'),
                TextEntry::make('origin_reversal_entry_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('counterparty_reversal_entry_id')
                    ->numeric()
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
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
