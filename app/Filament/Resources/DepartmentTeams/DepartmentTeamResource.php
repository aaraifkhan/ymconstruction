<?php

namespace App\Filament\Resources\DepartmentTeams;

use App\Filament\Resources\DepartmentTeams\Pages\CreateDepartmentTeam;
use App\Filament\Resources\DepartmentTeams\Pages\EditDepartmentTeam;
use App\Filament\Resources\DepartmentTeams\Pages\ListDepartmentTeams;
use App\Filament\Resources\DepartmentTeams\Pages\ViewDepartmentTeam;
use App\Filament\Resources\DepartmentTeams\RelationManagers\MembersRelationManager;
use App\Filament\Resources\DepartmentTeams\Schemas\DepartmentTeamForm;
use App\Filament\Resources\DepartmentTeams\Schemas\DepartmentTeamInfolist;
use App\Filament\Resources\DepartmentTeams\Tables\DepartmentTeamsTable;
use App\Models\DepartmentTeam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DepartmentTeamResource extends Resource
{
    protected static ?string $model = DepartmentTeam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'departmentTeams';

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DepartmentTeamForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepartmentTeamInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentTeamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartmentTeams::route('/'),
            'create' => CreateDepartmentTeam::route('/create'),
            'view' => ViewDepartmentTeam::route('/{record}'),
            'edit' => EditDepartmentTeam::route('/{record}/edit'),
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
