<?php

namespace App\Filament\Resources\EmployeeAssetCustodies;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\EmployeeAssetCustodies\Pages\CreateEmployeeAssetCustody;
use App\Filament\Resources\EmployeeAssetCustodies\Pages\EditEmployeeAssetCustody;
use App\Filament\Resources\EmployeeAssetCustodies\Pages\ListEmployeeAssetCustodies;
use App\Filament\Resources\EmployeeAssetCustodies\Pages\ViewEmployeeAssetCustody;
use App\Filament\Resources\EmployeeAssetCustodies\RelationManagers\EventsRelationManager;
use App\Filament\Resources\EmployeeAssetCustodies\Schemas\EmployeeAssetCustodyForm;
use App\Filament\Resources\EmployeeAssetCustodies\Schemas\EmployeeAssetCustodyInfolist;
use App\Filament\Resources\EmployeeAssetCustodies\Tables\EmployeeAssetCustodiesTable;
use App\Models\EmployeeAssetCustody;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeAssetCustodyResource extends Resource
{
    protected static ?string $model = EmployeeAssetCustody::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $tenantRelationshipName = 'employeeAssetCustodies';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Asset Issuance';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return EmployeeAssetCustodyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeAssetCustodyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeAssetCustodiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EventsRelationManager::class,
            RelatedDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAssetCustodies::route('/'),
            'create' => CreateEmployeeAssetCustody::route('/create'),
            'view' => ViewEmployeeAssetCustody::route('/{record}'),
            'edit' => EditEmployeeAssetCustody::route('/{record}/edit'),
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
