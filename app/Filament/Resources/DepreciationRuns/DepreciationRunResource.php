<?php

namespace App\Filament\Resources\DepreciationRuns;

use App\Filament\Resources\DepreciationRuns\Pages\CreateDepreciationRun;
use App\Filament\Resources\DepreciationRuns\Pages\EditDepreciationRun;
use App\Filament\Resources\DepreciationRuns\Pages\ListDepreciationRuns;
use App\Filament\Resources\DepreciationRuns\Pages\ViewDepreciationRun;
use App\Filament\Resources\DepreciationRuns\RelationManagers\LinesRelationManager;
use App\Filament\Resources\DepreciationRuns\Schemas\DepreciationRunForm;
use App\Filament\Resources\DepreciationRuns\Schemas\DepreciationRunInfolist;
use App\Filament\Resources\DepreciationRuns\Tables\DepreciationRunsTable;
use App\Models\DepreciationRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DepreciationRunResource extends Resource
{
    protected static ?string $model = DepreciationRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $tenantRelationshipName = 'depreciationRuns';

    protected static \UnitEnum|string|null $navigationGroup = 'Assets';

    public static function form(Schema $schema): Schema
    {
        return DepreciationRunForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepreciationRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepreciationRunsTable::configure($table);
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
            'index' => ListDepreciationRuns::route('/'),
            'create' => CreateDepreciationRun::route('/create'),
            'view' => ViewDepreciationRun::route('/{record}'),
            'edit' => EditDepreciationRun::route('/{record}/edit'),
        ];
    }
}
