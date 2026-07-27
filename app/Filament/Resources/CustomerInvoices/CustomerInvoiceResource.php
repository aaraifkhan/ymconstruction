<?php

namespace App\Filament\Resources\CustomerInvoices;

use App\Filament\Resources\CustomerInvoices\Pages\CreateCustomerInvoice;
use App\Filament\Resources\CustomerInvoices\Pages\EditCustomerInvoice;
use App\Filament\Resources\CustomerInvoices\Pages\ListCustomerInvoices;
use App\Filament\Resources\CustomerInvoices\Pages\ViewCustomerInvoice;
use App\Filament\Resources\CustomerInvoices\Schemas\CustomerInvoiceForm;
use App\Filament\Resources\CustomerInvoices\Schemas\CustomerInvoiceInfolist;
use App\Filament\Resources\CustomerInvoices\Tables\CustomerInvoicesTable;
use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Models\CustomerInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerInvoiceResource extends Resource
{
    protected static ?string $model = CustomerInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static ?string $tenantRelationshipName = 'customerInvoices';

    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Customer Invoices & Credit Notes';

    public static function form(Schema $schema): Schema
    {
        return CustomerInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [RelatedDocumentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerInvoices::route('/'),
            'create' => CreateCustomerInvoice::route('/create'),
            'view' => ViewCustomerInvoice::route('/{record}'),
            'edit' => EditCustomerInvoice::route('/{record}/edit'),
        ];
    }
}
