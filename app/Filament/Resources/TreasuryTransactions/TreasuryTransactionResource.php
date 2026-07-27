<?php

namespace App\Filament\Resources\TreasuryTransactions;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\TreasuryTransactions\Pages\CreateTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\EditTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\ListTreasuryTransactions;
use App\Filament\Resources\TreasuryTransactions\Pages\ViewTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Schemas\TreasuryTransactionForm;
use App\Filament\Resources\TreasuryTransactions\Schemas\TreasuryTransactionInfolist;
use App\Filament\Resources\TreasuryTransactions\Tables\TreasuryTransactionsTable;
use App\Models\TreasuryTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TreasuryTransactionResource extends Resource
{
    protected static ?string $model = TreasuryTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $recordTitleAttribute = 'transaction_number';

    protected static ?string $tenantRelationshipName = 'treasuryTransactions';

    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Payments, Receipts & Transfers';

    public static function form(Schema $schema): Schema
    {
        return TreasuryTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TreasuryTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreasuryTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [RelatedDocumentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTreasuryTransactions::route('/'),
            'create' => CreateTreasuryTransaction::route('/create'),
            'view' => ViewTreasuryTransaction::route('/{record}'),
            'edit' => EditTreasuryTransaction::route('/{record}/edit'),
        ];
    }
}
