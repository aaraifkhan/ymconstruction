<?php

namespace App\Filament\Resources\ProjectSites;

use App\Filament\Resources\ProjectSites\Pages\CreateProjectSite;
use App\Filament\Resources\ProjectSites\Pages\EditProjectSite;
use App\Filament\Resources\ProjectSites\Pages\ListProjectSites;
use App\Filament\Resources\ProjectSites\Pages\ViewProjectSite;
use App\Filament\Resources\ProjectSites\Schemas\ProjectSiteForm;
use App\Filament\Resources\ProjectSites\Schemas\ProjectSiteInfolist;
use App\Filament\Resources\ProjectSites\Tables\ProjectSitesTable;
use App\Models\ProjectSite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectSiteResource extends Resource
{
    protected static ?string $model = ProjectSite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'projectSites';

    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return ProjectSiteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectSiteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectSitesTable::configure($table);
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
            'index' => ListProjectSites::route('/'),
            'create' => CreateProjectSite::route('/create'),
            'view' => ViewProjectSite::route('/{record}'),
            'edit' => EditProjectSite::route('/{record}/edit'),
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
