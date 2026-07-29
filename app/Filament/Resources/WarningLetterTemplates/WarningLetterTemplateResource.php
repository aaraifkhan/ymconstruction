<?php

namespace App\Filament\Resources\WarningLetterTemplates;

use App\Filament\Resources\WarningLetterTemplates\Pages\CreateWarningLetterTemplate;
use App\Filament\Resources\WarningLetterTemplates\Pages\EditWarningLetterTemplate;
use App\Filament\Resources\WarningLetterTemplates\Pages\ListWarningLetterTemplates;
use App\Filament\Resources\WarningLetterTemplates\Pages\ViewWarningLetterTemplate;
use App\Filament\Resources\WarningLetterTemplates\Schemas\WarningLetterTemplateForm;
use App\Filament\Resources\WarningLetterTemplates\Schemas\WarningLetterTemplateInfolist;
use App\Filament\Resources\WarningLetterTemplates\Tables\WarningLetterTemplatesTable;
use App\Models\WarningLetterTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarningLetterTemplateResource extends Resource
{
    protected static ?string $model = WarningLetterTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'warningLetterTemplates';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return WarningLetterTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WarningLetterTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarningLetterTemplatesTable::configure($table);
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
            'index' => ListWarningLetterTemplates::route('/'),
            'create' => CreateWarningLetterTemplate::route('/create'),
            'view' => ViewWarningLetterTemplate::route('/{record}'),
            'edit' => EditWarningLetterTemplate::route('/{record}/edit'),
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
