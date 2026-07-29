<?php

namespace App\Filament\Resources\PerformanceAppraisals;

use App\Filament\Resources\PerformanceAppraisals\Pages\CreatePerformanceAppraisal;
use App\Filament\Resources\PerformanceAppraisals\Pages\EditPerformanceAppraisal;
use App\Filament\Resources\PerformanceAppraisals\Pages\ListPerformanceAppraisals;
use App\Filament\Resources\PerformanceAppraisals\Pages\ViewPerformanceAppraisal;
use App\Filament\Resources\PerformanceAppraisals\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PerformanceAppraisals\Schemas\PerformanceAppraisalForm;
use App\Filament\Resources\PerformanceAppraisals\Schemas\PerformanceAppraisalInfolist;
use App\Filament\Resources\PerformanceAppraisals\Tables\PerformanceAppraisalsTable;
use App\Models\PerformanceAppraisal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerformanceAppraisalResource extends Resource
{
    protected static ?string $model = PerformanceAppraisal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $tenantRelationshipName = 'performanceAppraisals';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return PerformanceAppraisalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerformanceAppraisalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceAppraisalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerformanceAppraisals::route('/'),
            'create' => CreatePerformanceAppraisal::route('/create'),
            'view' => ViewPerformanceAppraisal::route('/{record}'),
            'edit' => EditPerformanceAppraisal::route('/{record}/edit'),
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
