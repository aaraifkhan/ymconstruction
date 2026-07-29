<?php

namespace App\Filament\Resources\EmployeeWarnings;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\EmployeeWarnings\Pages\CreateEmployeeWarning;
use App\Filament\Resources\EmployeeWarnings\Pages\EditEmployeeWarning;
use App\Filament\Resources\EmployeeWarnings\Pages\ListEmployeeWarnings;
use App\Filament\Resources\EmployeeWarnings\Pages\ViewEmployeeWarning;
use App\Filament\Resources\EmployeeWarnings\Schemas\EmployeeWarningForm;
use App\Filament\Resources\EmployeeWarnings\Schemas\EmployeeWarningInfolist;
use App\Filament\Resources\EmployeeWarnings\Tables\EmployeeWarningsTable;
use App\Models\EmployeeWarning;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeWarningResource extends Resource
{
    protected static ?string $model = EmployeeWarning::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $tenantRelationshipName = 'employeeWarnings';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return EmployeeWarningForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeWarningInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeWarningsTable::configure($table);
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
            'index' => ListEmployeeWarnings::route('/'),
            'create' => CreateEmployeeWarning::route('/create'),
            'view' => ViewEmployeeWarning::route('/{record}'),
            'edit' => EditEmployeeWarning::route('/{record}/edit'),
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
