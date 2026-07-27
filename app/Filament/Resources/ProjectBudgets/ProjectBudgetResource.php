<?php

namespace App\Filament\Resources\ProjectBudgets;

use App\Filament\Resources\ProjectBudgets\Pages\CreateProjectBudget;
use App\Filament\Resources\ProjectBudgets\Pages\EditProjectBudget;
use App\Filament\Resources\ProjectBudgets\Pages\ListProjectBudgets;
use App\Filament\Resources\ProjectBudgets\Pages\ViewProjectBudget;
use App\Filament\Resources\ProjectBudgets\RelationManagers\LinesRelationManager;
use App\Filament\Resources\ProjectBudgets\Schemas\ProjectBudgetForm;
use App\Filament\Resources\ProjectBudgets\Schemas\ProjectBudgetInfolist;
use App\Filament\Resources\ProjectBudgets\Tables\ProjectBudgetsTable;
use App\Models\ProjectBudget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectBudgetResource extends Resource
{
    protected static ?string $model = ProjectBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $recordTitleAttribute = 'version';

    protected static ?string $tenantRelationshipName = 'projectBudgets';

    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return ProjectBudgetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectBudgetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectBudgetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectBudgets::route('/'),
            'create' => CreateProjectBudget::route('/create'),
            'view' => ViewProjectBudget::route('/{record}'),
            'edit' => EditProjectBudget::route('/{record}/edit'),
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
