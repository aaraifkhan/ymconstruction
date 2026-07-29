<?php

namespace App\Filament\Resources\LeaveLedgerEntries;

use App\Filament\Resources\LeaveLedgerEntries\Pages\CreateLeaveLedgerEntry;
use App\Filament\Resources\LeaveLedgerEntries\Pages\EditLeaveLedgerEntry;
use App\Filament\Resources\LeaveLedgerEntries\Pages\ListLeaveLedgerEntries;
use App\Filament\Resources\LeaveLedgerEntries\Pages\ViewLeaveLedgerEntry;
use App\Filament\Resources\LeaveLedgerEntries\Schemas\LeaveLedgerEntryForm;
use App\Filament\Resources\LeaveLedgerEntries\Schemas\LeaveLedgerEntryInfolist;
use App\Filament\Resources\LeaveLedgerEntries\Tables\LeaveLedgerEntriesTable;
use App\Models\LeaveLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveLedgerEntryResource extends Resource
{
    protected static ?string $model = LeaveLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $tenantRelationshipName = 'leaveLedgerEntries';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return LeaveLedgerEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveLedgerEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveLedgerEntriesTable::configure($table);
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
            'index' => ListLeaveLedgerEntries::route('/'),
            'create' => CreateLeaveLedgerEntry::route('/create'),
            'view' => ViewLeaveLedgerEntry::route('/{record}'),
            'edit' => EditLeaveLedgerEntry::route('/{record}/edit'),
        ];
    }
}
