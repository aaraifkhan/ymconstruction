<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings;

use App\Filament\Resources\FinalSettlementAccountMappings\Pages\CreateFinalSettlementAccountMapping;
use App\Filament\Resources\FinalSettlementAccountMappings\Pages\EditFinalSettlementAccountMapping;
use App\Filament\Resources\FinalSettlementAccountMappings\Pages\ListFinalSettlementAccountMappings;
use App\Filament\Resources\FinalSettlementAccountMappings\Pages\ViewFinalSettlementAccountMapping;
use App\Filament\Resources\FinalSettlementAccountMappings\Schemas\FinalSettlementAccountMappingForm;
use App\Filament\Resources\FinalSettlementAccountMappings\Schemas\FinalSettlementAccountMappingInfolist;
use App\Filament\Resources\FinalSettlementAccountMappings\Tables\FinalSettlementAccountMappingsTable;
use App\Models\FinalSettlementAccountMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinalSettlementAccountMappingResource extends Resource
{
    protected static ?string $model = FinalSettlementAccountMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'finalSettlementAccountMappings';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Configuration';

    public static function form(Schema $schema): Schema
    {
        return FinalSettlementAccountMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinalSettlementAccountMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinalSettlementAccountMappingsTable::configure($table);
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
            'index' => ListFinalSettlementAccountMappings::route('/'),
            'create' => CreateFinalSettlementAccountMapping::route('/create'),
            'view' => ViewFinalSettlementAccountMapping::route('/{record}'),
            'edit' => EditFinalSettlementAccountMapping::route('/{record}/edit'),
        ];
    }
}
