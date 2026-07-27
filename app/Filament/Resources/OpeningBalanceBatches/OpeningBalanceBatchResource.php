<?php

namespace App\Filament\Resources\OpeningBalanceBatches;

use App\Filament\Resources\OpeningBalanceBatches\Pages\CreateOpeningBalanceBatch;
use App\Filament\Resources\OpeningBalanceBatches\Pages\EditOpeningBalanceBatch;
use App\Filament\Resources\OpeningBalanceBatches\Pages\ListOpeningBalanceBatches;
use App\Filament\Resources\OpeningBalanceBatches\Pages\ViewOpeningBalanceBatch;
use App\Filament\Resources\OpeningBalanceBatches\Schemas\OpeningBalanceBatchForm;
use App\Filament\Resources\OpeningBalanceBatches\Schemas\OpeningBalanceBatchInfolist;
use App\Filament\Resources\OpeningBalanceBatches\Tables\OpeningBalanceBatchesTable;
use App\Models\OpeningBalanceBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpeningBalanceBatchResource extends Resource
{
    protected static ?string $model = OpeningBalanceBatch::class;

    protected static ?string $tenantRelationshipName = 'openingBalanceBatches';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Opening Balances';

    protected static ?string $recordTitleAttribute = 'source_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function form(Schema $schema): Schema
    {
        return OpeningBalanceBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpeningBalanceBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningBalanceBatchesTable::configure($table);
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
            'index' => ListOpeningBalanceBatches::route('/'),
            'create' => CreateOpeningBalanceBatch::route('/create'),
            'view' => ViewOpeningBalanceBatch::route('/{record}'),
            'edit' => EditOpeningBalanceBatch::route('/{record}/edit'),
        ];
    }
}
