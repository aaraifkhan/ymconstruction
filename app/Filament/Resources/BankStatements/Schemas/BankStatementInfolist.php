<?php

namespace App\Filament\Resources\BankStatements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankStatementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Private bank statement')->columns(4)->schema([
                TextEntry::make('companyBankAccount.bank_name')->label('Bank'),
                TextEntry::make('companyBankAccount.account_title')->label('Account title'),
                TextEntry::make('period_start')->date(),
                TextEntry::make('period_end')->date(),
                TextEntry::make('opening_balance')->money('PKR'),
                TextEntry::make('closing_balance')->money('PKR'),
                TextEntry::make('status')->badge(),
                TextEntry::make('source_file_name')->placeholder('-'),
                TextEntry::make('source_sha256')->label('Source SHA-256')->columnSpanFull()->placeholder('-'),
            ]),
        ]);
    }
}
