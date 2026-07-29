<?php

namespace App\Filament\Resources\EmploymentSeparations;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\EmploymentSeparations\Pages\CreateEmploymentSeparation;
use App\Filament\Resources\EmploymentSeparations\Pages\EditEmploymentSeparation;
use App\Filament\Resources\EmploymentSeparations\Pages\ListEmploymentSeparations;
use App\Filament\Resources\EmploymentSeparations\Pages\ViewEmploymentSeparation;
use App\Filament\Resources\EmploymentSeparations\Schemas\EmploymentSeparationForm;
use App\Filament\Resources\EmploymentSeparations\Schemas\EmploymentSeparationInfolist;
use App\Filament\Resources\EmploymentSeparations\Tables\EmploymentSeparationsTable;
use App\Models\EmploymentSeparation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentSeparationResource extends Resource
{
    protected static ?string $model = EmploymentSeparation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $tenantRelationshipName = 'employmentSeparations';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return EmploymentSeparationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmploymentSeparationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmploymentSeparationsTable::configure($table);
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
            'index' => ListEmploymentSeparations::route('/'),
            'create' => CreateEmploymentSeparation::route('/create'),
            'view' => ViewEmploymentSeparation::route('/{record}'),
            'edit' => EditEmploymentSeparation::route('/{record}/edit'),
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
