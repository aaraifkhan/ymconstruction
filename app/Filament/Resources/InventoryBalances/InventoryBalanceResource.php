<?php

namespace App\Filament\Resources\InventoryBalances;

use App\Filament\Resources\InventoryBalances\Pages\ListInventoryBalances;
use App\Filament\Resources\InventoryBalances\Pages\ViewInventoryBalance;
use App\Filament\Resources\InventoryBalances\Schemas\InventoryBalanceForm;
use App\Filament\Resources\InventoryBalances\Schemas\InventoryBalanceInfolist;
use App\Filament\Resources\InventoryBalances\Tables\InventoryBalancesTable;
use App\Models\InventoryBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryBalanceResource extends Resource
{
    protected static ?string $model = InventoryBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $tenantRelationshipName = 'inventoryBalances';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Site Inventory Balances';

    public static function form(Schema $schema): Schema
    {
        return InventoryBalanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryBalanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryBalancesTable::configure($table);
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
            'index' => ListInventoryBalances::route('/'),
            'view' => ViewInventoryBalance::route('/{record}'),
        ];
    }
}
