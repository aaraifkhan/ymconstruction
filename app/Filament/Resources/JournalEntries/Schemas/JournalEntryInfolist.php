<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JournalEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Voucher')->columns(3)->schema([
                TextEntry::make('voucher_number')->placeholder('Pending posting'),
                TextEntry::make('voucher_type')->badge(),
                TextEntry::make('status')->badge()->color(fn ($state) => $state->color()),
                TextEntry::make('transaction_date')->date(),
                TextEntry::make('reference')->placeholder('—'),
                TextEntry::make('currency_code'),
                TextEntry::make('description')->columnSpanFull(),
                TextEntry::make('debit_total')->numeric(decimalPlaces: 2),
                TextEntry::make('credit_total')->numeric(decimalPlaces: 2),
            ]),
            Section::make('Journal lines')->schema([
                RepeatableEntry::make('lines')->schema([
                    TextEntry::make('line_number'),
                    TextEntry::make('account_code_snapshot')->label('Account code'),
                    TextEntry::make('account_name_snapshot')->label('Account'),
                    TextEntry::make('debit')->numeric(decimalPlaces: 2),
                    TextEntry::make('credit')->numeric(decimalPlaces: 2),
                    TextEntry::make('party.name')->placeholder('—'),
                    TextEntry::make('project.name')->placeholder('—'),
                    TextEntry::make('costCenter.name')->placeholder('—'),
                ])->columns(4),
            ]),
            Section::make('Workflow evidence')->columns(3)->schema([
                TextEntry::make('preparedBy.name'), TextEntry::make('submittedBy.name')->placeholder('—'), TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                TextEntry::make('approvedBy.name')->placeholder('—'), TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                TextEntry::make('postedBy.name')->placeholder('—'), TextEntry::make('posted_at')->dateTime()->placeholder('—'),
                TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }
}
