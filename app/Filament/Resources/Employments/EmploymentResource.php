<?php

namespace App\Filament\Resources\Employments;

use App\Filament\Resources\Employments\Pages\CreateEmployment;
use App\Filament\Resources\Employments\Pages\EditEmployment;
use App\Filament\Resources\Employments\Pages\ListEmployments;
use App\Filament\Resources\Employments\Pages\ViewEmployment;
use App\Filament\Resources\Employments\RelationManagers\ChangesRelationManager;
use App\Filament\Resources\Employments\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Employments\Schemas\EmploymentForm;
use App\Filament\Resources\Employments\Schemas\EmploymentInfolist;
use App\Filament\Resources\Employments\Tables\EmploymentsTable;
use App\Models\Employment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentResource extends Resource
{
    protected static ?string $model = Employment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'employee_code';

    protected static ?string $tenantRelationshipName = 'employments';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return EmploymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmploymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmploymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ChangesRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployments::route('/'),
            'create' => CreateEmployment::route('/create'),
            'view' => ViewEmployment::route('/{record}'),
            'edit' => EditEmployment::route('/{record}/edit'),
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
