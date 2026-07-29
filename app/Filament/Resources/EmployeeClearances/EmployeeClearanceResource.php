<?php

namespace App\Filament\Resources\EmployeeClearances;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\EmployeeClearances\Pages\ListEmployeeClearances;
use App\Filament\Resources\EmployeeClearances\Pages\ViewEmployeeClearance;
use App\Filament\Resources\EmployeeClearances\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\EmployeeClearances\Schemas\EmployeeClearanceForm;
use App\Filament\Resources\EmployeeClearances\Schemas\EmployeeClearanceInfolist;
use App\Filament\Resources\EmployeeClearances\Tables\EmployeeClearancesTable;
use App\Models\EmployeeClearance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeClearanceResource extends Resource
{
    protected static ?string $model = EmployeeClearance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'employeeClearances';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return EmployeeClearanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeClearanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeClearancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            RelatedDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeClearances::route('/'),
            'view' => ViewEmployeeClearance::route('/{record}'),
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
