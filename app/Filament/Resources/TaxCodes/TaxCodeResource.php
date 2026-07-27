<?php

namespace App\Filament\Resources\TaxCodes;

use App\Filament\Resources\TaxCodes\Pages\CreateTaxCode;
use App\Filament\Resources\TaxCodes\Pages\EditTaxCode;
use App\Filament\Resources\TaxCodes\Pages\ListTaxCodes;
use App\Filament\Resources\TaxCodes\Pages\ViewTaxCode;
use App\Filament\Resources\TaxCodes\Schemas\TaxCodeForm;
use App\Filament\Resources\TaxCodes\Schemas\TaxCodeInfolist;
use App\Filament\Resources\TaxCodes\Tables\TaxCodesTable;
use App\Models\TaxCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxCodeResource extends Resource
{
    protected static ?string $model = TaxCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'taxCodes';

    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return TaxCodeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxCodeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxCodesTable::configure($table);
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
            'index' => ListTaxCodes::route('/'),
            'create' => CreateTaxCode::route('/create'),
            'view' => ViewTaxCode::route('/{record}'),
            'edit' => EditTaxCode::route('/{record}/edit'),
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
