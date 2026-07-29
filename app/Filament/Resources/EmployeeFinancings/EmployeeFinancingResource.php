<?php

namespace App\Filament\Resources\EmployeeFinancings;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\EmployeeFinancings\Pages\CreateEmployeeFinancing;
use App\Filament\Resources\EmployeeFinancings\Pages\EditEmployeeFinancing;
use App\Filament\Resources\EmployeeFinancings\Pages\ListEmployeeFinancings;
use App\Filament\Resources\EmployeeFinancings\Pages\ViewEmployeeFinancing;
use App\Filament\Resources\EmployeeFinancings\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\EmployeeFinancings\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\EmployeeFinancings\Schemas\EmployeeFinancingForm;
use App\Filament\Resources\EmployeeFinancings\Schemas\EmployeeFinancingInfolist;
use App\Filament\Resources\EmployeeFinancings\Tables\EmployeeFinancingsTable;
use App\Models\EmployeeFinancing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeFinancingResource extends Resource
{
    protected static ?string $model = EmployeeFinancing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'employeeFinancings';

    protected static \UnitEnum|string|null $navigationGroup = 'Loans & Advances';

    protected static ?string $navigationLabel = 'Employee Loans & Advances';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return EmployeeFinancingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeFinancingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeFinancingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InstallmentsRelationManager::class,
            TransactionsRelationManager::class,
            RelatedDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeFinancings::route('/'),
            'create' => CreateEmployeeFinancing::route('/create'),
            'view' => ViewEmployeeFinancing::route('/{record}'),
            'edit' => EditEmployeeFinancing::route('/{record}/edit'),
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
