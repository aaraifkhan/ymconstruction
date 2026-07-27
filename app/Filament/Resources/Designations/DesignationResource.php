<?php

namespace App\Filament\Resources\Designations;

use App\Filament\Resources\Designations\Pages\CreateDesignation;
use App\Filament\Resources\Designations\Pages\EditDesignation;
use App\Filament\Resources\Designations\Pages\ListDesignations;
use App\Filament\Resources\Designations\Pages\ViewDesignation;
use App\Filament\Resources\Designations\Schemas\DesignationForm;
use App\Filament\Resources\Designations\Schemas\DesignationInfolist;
use App\Filament\Resources\Designations\Tables\DesignationsTable;
use App\Models\Designation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DesignationResource extends Resource
{
    protected static ?string $model = Designation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'designations';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return DesignationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DesignationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignationsTable::configure($table);
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
            'index' => ListDesignations::route('/'),
            'create' => CreateDesignation::route('/create'),
            'view' => ViewDesignation::route('/{record}'),
            'edit' => EditDesignation::route('/{record}/edit'),
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
