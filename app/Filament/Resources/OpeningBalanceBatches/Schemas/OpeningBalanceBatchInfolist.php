<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpeningBalanceBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Opening balance')->columns(3)->schema([
                TextEntry::make('source_name')->placeholder('Manual opening balance'),
                TextEntry::make('opening_date')->date(),
                TextEntry::make('status')->badge(),
                TextEntry::make('debit_total')->numeric(decimalPlaces: 2),
                TextEntry::make('credit_total')->numeric(decimalPlaces: 2),
                TextEntry::make('journalEntry.voucher_number')->placeholder('Not posted'),
            ]),
            Section::make('Lines')->schema([
                RepeatableEntry::make('lines')->schema([
                    TextEntry::make('line_number'), TextEntry::make('account.code'),
                    TextEntry::make('account.name'), TextEntry::make('debit')->numeric(decimalPlaces: 2),
                    TextEntry::make('credit')->numeric(decimalPlaces: 2), TextEntry::make('party.name')->placeholder('—'),
                    TextEntry::make('project.name')->placeholder('—'), TextEntry::make('costCenter.name')->placeholder('—'),
                ])->columns(4),
            ]),
        ]);
    }
}
