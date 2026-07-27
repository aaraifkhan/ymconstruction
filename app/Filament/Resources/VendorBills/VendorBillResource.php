<?php

namespace App\Filament\Resources\VendorBills;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Resources\VendorBills\Pages\EditVendorBill;
use App\Filament\Resources\VendorBills\Pages\ListVendorBills;
use App\Filament\Resources\VendorBills\Pages\ViewVendorBill;
use App\Filament\Resources\VendorBills\Schemas\VendorBillForm;
use App\Filament\Resources\VendorBills\Schemas\VendorBillInfolist;
use App\Filament\Resources\VendorBills\Tables\VendorBillsTable;
use App\Models\VendorBill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static ?string $recordTitleAttribute = 'vendor_bill_number';

    protected static ?string $tenantRelationshipName = 'vendorBills';

    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Vendor Bills & Credit Notes';

    public static function form(Schema $schema): Schema
    {
        return VendorBillForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VendorBillInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorBillsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [RelatedDocumentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorBills::route('/'),
            'create' => CreateVendorBill::route('/create'),
            'view' => ViewVendorBill::route('/{record}'),
            'edit' => EditVendorBill::route('/{record}/edit'),
        ];
    }
}
