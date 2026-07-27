<?php

namespace App\Filament\Resources\JournalEntries;

use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Filament\Resources\JournalEntries\Schemas\JournalEntryForm;
use App\Filament\Resources\JournalEntries\Schemas\JournalEntryInfolist;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $tenantRelationshipName = 'journalEntries';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Vouchers / Journals';

    protected static ?string $recordTitleAttribute = 'voucher_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return JournalEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JournalEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view' => ViewJournalEntry::route('/{record}'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
