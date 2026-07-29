<?php

namespace App\Filament\Resources\PerformanceKpis;

use App\Filament\Resources\PerformanceKpis\Pages\CreatePerformanceKpi;
use App\Filament\Resources\PerformanceKpis\Pages\EditPerformanceKpi;
use App\Filament\Resources\PerformanceKpis\Pages\ListPerformanceKpis;
use App\Filament\Resources\PerformanceKpis\Pages\ViewPerformanceKpi;
use App\Filament\Resources\PerformanceKpis\Schemas\PerformanceKpiForm;
use App\Filament\Resources\PerformanceKpis\Schemas\PerformanceKpiInfolist;
use App\Filament\Resources\PerformanceKpis\Tables\PerformanceKpisTable;
use App\Models\PerformanceKpi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerformanceKpiResource extends Resource
{
    protected static ?string $model = PerformanceKpi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'performanceKpis';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return PerformanceKpiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerformanceKpiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceKpisTable::configure($table);
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
            'index' => ListPerformanceKpis::route('/'),
            'create' => CreatePerformanceKpi::route('/create'),
            'view' => ViewPerformanceKpi::route('/{record}'),
            'edit' => EditPerformanceKpi::route('/{record}/edit'),
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
