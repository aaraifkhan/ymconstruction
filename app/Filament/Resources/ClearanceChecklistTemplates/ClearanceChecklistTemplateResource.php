<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates;

use App\Filament\Resources\ClearanceChecklistTemplates\Pages\CreateClearanceChecklistTemplate;
use App\Filament\Resources\ClearanceChecklistTemplates\Pages\EditClearanceChecklistTemplate;
use App\Filament\Resources\ClearanceChecklistTemplates\Pages\ListClearanceChecklistTemplates;
use App\Filament\Resources\ClearanceChecklistTemplates\Pages\ViewClearanceChecklistTemplate;
use App\Filament\Resources\ClearanceChecklistTemplates\Schemas\ClearanceChecklistTemplateForm;
use App\Filament\Resources\ClearanceChecklistTemplates\Schemas\ClearanceChecklistTemplateInfolist;
use App\Filament\Resources\ClearanceChecklistTemplates\Tables\ClearanceChecklistTemplatesTable;
use App\Models\ClearanceChecklistTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClearanceChecklistTemplateResource extends Resource
{
    protected static ?string $model = ClearanceChecklistTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $tenantRelationshipName = 'clearanceChecklistTemplates';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClearanceChecklistTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClearanceChecklistTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClearanceChecklistTemplatesTable::configure($table);
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
            'index' => ListClearanceChecklistTemplates::route('/'),
            'create' => CreateClearanceChecklistTemplate::route('/create'),
            'view' => ViewClearanceChecklistTemplate::route('/{record}'),
            'edit' => EditClearanceChecklistTemplate::route('/{record}/edit'),
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
