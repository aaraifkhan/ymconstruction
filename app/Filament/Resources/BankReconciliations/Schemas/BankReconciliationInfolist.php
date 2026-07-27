<?php

namespace App\Filament\Resources\BankReconciliations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankReconciliationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank reconciliation')->columns(4)->schema([
                TextEntry::make('companyBankAccount.bank_name')->label('Bank'),
                TextEntry::make('period_start')->date(),
                TextEntry::make('period_end')->date(),
                TextEntry::make('status')->badge(),
                TextEntry::make('statement_closing_balance')->money('PKR'),
                TextEntry::make('book_closing_balance')->money('PKR'),
                TextEntry::make('difference')->money('PKR'),
                TextEntry::make('bankStatement.source_sha256')->label('Statement SHA-256')->columnSpanFull(),
                TextEntry::make('reopen_reason')->columnSpanFull()->placeholder('-'),
            ]),
        ]);
    }
}
