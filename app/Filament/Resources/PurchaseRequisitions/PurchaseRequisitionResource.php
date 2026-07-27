<?php

namespace App\Filament\Resources\PurchaseRequisitions;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\PurchaseRequisitions\Pages\CreatePurchaseRequisition;
use App\Filament\Resources\PurchaseRequisitions\Pages\EditPurchaseRequisition;
use App\Filament\Resources\PurchaseRequisitions\Pages\ListPurchaseRequisitions;
use App\Filament\Resources\PurchaseRequisitions\Pages\ViewPurchaseRequisition;
use App\Filament\Resources\PurchaseRequisitions\Schemas\PurchaseRequisitionForm;
use App\Filament\Resources\PurchaseRequisitions\Schemas\PurchaseRequisitionInfolist;
use App\Filament\Resources\PurchaseRequisitions\Tables\PurchaseRequisitionsTable;
use App\Models\PurchaseRequisition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseRequisitionResource extends Resource
{
    protected static ?string $model = PurchaseRequisition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'requisition_number';

    protected static ?string $tenantRelationshipName = 'purchaseRequisitions';

    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Purchase Requisitions';

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequisitionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseRequisitionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequisitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelatedDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequisitions::route('/'),
            'create' => CreatePurchaseRequisition::route('/create'),
            'view' => ViewPurchaseRequisition::route('/{record}'),
            'edit' => EditPurchaseRequisition::route('/{record}/edit'),
        ];
    }
}
