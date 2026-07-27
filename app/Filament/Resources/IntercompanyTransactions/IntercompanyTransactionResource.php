<?php

namespace App\Filament\Resources\IntercompanyTransactions;

use App\Filament\Resources\IntercompanyTransactions\Pages\CreateIntercompanyTransaction;
use App\Filament\Resources\IntercompanyTransactions\Pages\EditIntercompanyTransaction;
use App\Filament\Resources\IntercompanyTransactions\Pages\ListIntercompanyTransactions;
use App\Filament\Resources\IntercompanyTransactions\Pages\ViewIntercompanyTransaction;
use App\Filament\Resources\IntercompanyTransactions\Schemas\IntercompanyTransactionForm;
use App\Filament\Resources\IntercompanyTransactions\Schemas\IntercompanyTransactionInfolist;
use App\Filament\Resources\IntercompanyTransactions\Tables\IntercompanyTransactionsTable;
use App\Models\IntercompanyTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IntercompanyTransactionResource extends Resource
{
    protected static ?string $model = IntercompanyTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $tenantRelationshipName = 'originatedIntercompanyTransactions';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Inter-company';

    public static function form(Schema $schema): Schema
    {
        return IntercompanyTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IntercompanyTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntercompanyTransactionsTable::configure($table);
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
            'index' => ListIntercompanyTransactions::route('/'),
            'create' => CreateIntercompanyTransaction::route('/create'),
            'view' => ViewIntercompanyTransaction::route('/{record}'),
            'edit' => EditIntercompanyTransaction::route('/{record}/edit'),
        ];
    }

    public static function scopeEloquentQueryToTenant(Builder $query, ?Model $tenant): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('company_id', $tenant?->getKey())
            ->orWhere('counterparty_company_id', $tenant?->getKey()));
    }
}
