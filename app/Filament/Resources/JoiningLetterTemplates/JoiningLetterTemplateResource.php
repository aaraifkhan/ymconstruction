<?php

namespace App\Filament\Resources\JoiningLetterTemplates;

use App\Filament\Resources\JoiningLetterTemplates\Pages\CreateJoiningLetterTemplate;
use App\Filament\Resources\JoiningLetterTemplates\Pages\EditJoiningLetterTemplate;
use App\Filament\Resources\JoiningLetterTemplates\Pages\ListJoiningLetterTemplates;
use App\Filament\Resources\JoiningLetterTemplates\Pages\ViewJoiningLetterTemplate;
use App\Filament\Resources\JoiningLetterTemplates\Schemas\JoiningLetterTemplateForm;
use App\Filament\Resources\JoiningLetterTemplates\Schemas\JoiningLetterTemplateInfolist;
use App\Filament\Resources\JoiningLetterTemplates\Tables\JoiningLetterTemplatesTable;
use App\Models\JoiningLetterTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JoiningLetterTemplateResource extends Resource
{
    protected static ?string $model = JoiningLetterTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'joiningLetterTemplates';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Joining Letter Templates';

    public static function form(Schema $schema): Schema
    {
        return JoiningLetterTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JoiningLetterTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JoiningLetterTemplatesTable::configure($table);
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
            'index' => ListJoiningLetterTemplates::route('/'),
            'create' => CreateJoiningLetterTemplate::route('/create'),
            'view' => ViewJoiningLetterTemplate::route('/{record}'),
            'edit' => EditJoiningLetterTemplate::route('/{record}/edit'),
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
