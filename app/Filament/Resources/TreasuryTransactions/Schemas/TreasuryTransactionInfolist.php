<?php

namespace App\Filament\Resources\TreasuryTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TreasuryTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment / receipt / transfer')->columns(4)->schema([
                TextEntry::make('transaction_number')->placeholder('Draft'),
                TextEntry::make('type')->badge(),
                TextEntry::make('purpose')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('transaction_date')->date(),
                TextEntry::make('value_date')->date()->placeholder('-'),
                TextEntry::make('party.name')->label('Party')->placeholder('-'),
                TextEntry::make('employment.employee.full_name')->label('Employee')->placeholder('-'),
                TextEntry::make('amount')->money('PKR'),
                TextEntry::make('allocated_amount')->money('PKR'),
                TextEntry::make('unallocated_amount')->money('PKR'),
                TextEntry::make('instrument_type')->badge(),
                TextEntry::make('instrument_number')->placeholder('-'),
                TextEntry::make('bank_reference')->placeholder('-'),
                TextEntry::make('journalEntry.voucher_number')->label('Posted voucher')->placeholder('-'),
                TextEntry::make('reversalJournalEntry.voucher_number')->label('Reversal voucher')->placeholder('-'),
                TextEntry::make('description')->columnSpanFull(),
                TextEntry::make('rejection_reason')->columnSpanFull()->placeholder('-'),
            ]),
        ]);
    }
}
