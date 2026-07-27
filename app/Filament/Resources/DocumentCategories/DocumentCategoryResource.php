<?php

namespace App\Filament\Resources\DocumentCategories;

use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\ListDocumentCategories;
use App\Filament\Resources\DocumentCategories\Pages\ViewDocumentCategory;
use App\Filament\Resources\DocumentCategories\Schemas\DocumentCategoryForm;
use App\Filament\Resources\DocumentCategories\Schemas\DocumentCategoryInfolist;
use App\Filament\Resources\DocumentCategories\Tables\DocumentCategoriesTable;
use App\Models\DocumentCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentCategoryResource extends Resource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'documentCategories';

    protected static \UnitEnum|string|null $navigationGroup = 'Document Management';

    protected static ?string $navigationLabel = 'Document Categories';

    public static function form(Schema $schema): Schema
    {
        return DocumentCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentCategoriesTable::configure($table);
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
            'index' => ListDocumentCategories::route('/'),
            'create' => CreateDocumentCategory::route('/create'),
            'view' => ViewDocumentCategory::route('/{record}'),
            'edit' => EditDocumentCategory::route('/{record}/edit'),
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
