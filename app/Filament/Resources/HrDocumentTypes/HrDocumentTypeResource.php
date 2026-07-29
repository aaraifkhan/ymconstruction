<?php

namespace App\Filament\Resources\HrDocumentTypes;

use App\Filament\Resources\HrDocumentTypes\Pages\CreateHrDocumentType;
use App\Filament\Resources\HrDocumentTypes\Pages\EditHrDocumentType;
use App\Filament\Resources\HrDocumentTypes\Pages\ListHrDocumentTypes;
use App\Filament\Resources\HrDocumentTypes\Pages\ViewHrDocumentType;
use App\Filament\Resources\HrDocumentTypes\Schemas\HrDocumentTypeForm;
use App\Filament\Resources\HrDocumentTypes\Schemas\HrDocumentTypeInfolist;
use App\Filament\Resources\HrDocumentTypes\Tables\HrDocumentTypesTable;
use App\Models\HrDocumentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HrDocumentTypeResource extends Resource
{
    protected static ?string $model = HrDocumentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'hrDocumentTypes';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return HrDocumentTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HrDocumentTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrDocumentTypesTable::configure($table);
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
            'index' => ListHrDocumentTypes::route('/'),
            'create' => CreateHrDocumentType::route('/create'),
            'view' => ViewHrDocumentType::route('/{record}'),
            'edit' => EditHrDocumentType::route('/{record}/edit'),
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
