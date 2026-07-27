<?php

namespace App\Filament\Resources\AccountTemplates;

use App\Filament\Resources\AccountTemplates\Pages\CreateAccountTemplate;
use App\Filament\Resources\AccountTemplates\Pages\EditAccountTemplate;
use App\Filament\Resources\AccountTemplates\Pages\ListAccountTemplates;
use App\Filament\Resources\AccountTemplates\Pages\ViewAccountTemplate;
use App\Filament\Resources\AccountTemplates\Schemas\AccountTemplateForm;
use App\Filament\Resources\AccountTemplates\Schemas\AccountTemplateInfolist;
use App\Filament\Resources\AccountTemplates\Tables\AccountTemplatesTable;
use App\Models\AccountTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountTemplateResource extends Resource
{
    protected static ?string $model = AccountTemplate::class;

    protected static bool $isScopedToTenant = false;

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return AccountTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccountTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountTemplatesTable::configure($table);
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
            'index' => ListAccountTemplates::route('/'),
            'create' => CreateAccountTemplate::route('/create'),
            'view' => ViewAccountTemplate::route('/{record}'),
            'edit' => EditAccountTemplate::route('/{record}/edit'),
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
