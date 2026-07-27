<?php

namespace App\Filament\Resources\CompanyBankAccounts;

use App\Filament\Resources\CompanyBankAccounts\Pages\CreateCompanyBankAccount;
use App\Filament\Resources\CompanyBankAccounts\Pages\EditCompanyBankAccount;
use App\Filament\Resources\CompanyBankAccounts\Pages\ListCompanyBankAccounts;
use App\Filament\Resources\CompanyBankAccounts\Pages\ViewCompanyBankAccount;
use App\Filament\Resources\CompanyBankAccounts\Schemas\CompanyBankAccountForm;
use App\Filament\Resources\CompanyBankAccounts\Schemas\CompanyBankAccountInfolist;
use App\Filament\Resources\CompanyBankAccounts\Tables\CompanyBankAccountsTable;
use App\Models\CompanyBankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyBankAccountResource extends Resource
{
    protected static ?string $model = CompanyBankAccount::class;

    protected static ?string $recordTitleAttribute = 'bank_name';

    protected static ?string $tenantRelationshipName = 'bankAccounts';

    protected static \UnitEnum|string|null $navigationGroup = 'Company Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return CompanyBankAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyBankAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyBankAccountsTable::configure($table);
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
            'index' => ListCompanyBankAccounts::route('/'),
            'create' => CreateCompanyBankAccount::route('/create'),
            'view' => ViewCompanyBankAccount::route('/{record}'),
            'edit' => EditCompanyBankAccount::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
