<?php

namespace App\Filament\Resources\AccountingMappings;

use App\Filament\Resources\AccountingMappings\Pages\CreateAccountingMapping;
use App\Filament\Resources\AccountingMappings\Pages\EditAccountingMapping;
use App\Filament\Resources\AccountingMappings\Pages\ListAccountingMappings;
use App\Filament\Resources\AccountingMappings\Pages\ViewAccountingMapping;
use App\Filament\Resources\AccountingMappings\Schemas\AccountingMappingForm;
use App\Filament\Resources\AccountingMappings\Schemas\AccountingMappingInfolist;
use App\Filament\Resources\AccountingMappings\Tables\AccountingMappingsTable;
use App\Models\AccountingMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountingMappingResource extends Resource
{
    protected static ?string $model = AccountingMapping::class;

    protected static ?string $tenantRelationshipName = 'accountingMappings';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function form(Schema $schema): Schema
    {
        return AccountingMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccountingMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountingMappingsTable::configure($table);
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
            'index' => ListAccountingMappings::route('/'),
            'create' => CreateAccountingMapping::route('/create'),
            'view' => ViewAccountingMapping::route('/{record}'),
            'edit' => EditAccountingMapping::route('/{record}/edit'),
        ];
    }
}
