<?php

namespace App\Filament\Resources\FinalSettlements;

use App\Filament\Resources\Documents\RelationManagers\RelatedDocumentsRelationManager;
use App\Filament\Resources\FinalSettlements\Pages\ListFinalSettlements;
use App\Filament\Resources\FinalSettlements\Pages\ViewFinalSettlement;
use App\Filament\Resources\FinalSettlements\RelationManagers\LinesRelationManager;
use App\Filament\Resources\FinalSettlements\Schemas\FinalSettlementForm;
use App\Filament\Resources\FinalSettlements\Schemas\FinalSettlementInfolist;
use App\Filament\Resources\FinalSettlements\Tables\FinalSettlementsTable;
use App\Models\FinalSettlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinalSettlementResource extends Resource
{
    protected static ?string $model = FinalSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'finalSettlements';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return FinalSettlementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinalSettlementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinalSettlementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
            RelatedDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinalSettlements::route('/'),
            'view' => ViewFinalSettlement::route('/{record}'),
        ];
    }
}
