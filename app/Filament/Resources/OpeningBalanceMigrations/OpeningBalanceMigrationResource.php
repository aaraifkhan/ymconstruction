<?php

namespace App\Filament\Resources\OpeningBalanceMigrations;

use App\Filament\Resources\OpeningBalanceMigrations\Pages\ListOpeningBalanceMigrations;
use App\Filament\Resources\OpeningBalanceMigrations\Pages\ViewOpeningBalanceMigration;
use App\Filament\Resources\OpeningBalanceMigrations\Schemas\OpeningBalanceMigrationForm;
use App\Filament\Resources\OpeningBalanceMigrations\Schemas\OpeningBalanceMigrationInfolist;
use App\Filament\Resources\OpeningBalanceMigrations\Tables\OpeningBalanceMigrationsTable;
use App\Models\OpeningBalanceMigration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpeningBalanceMigrationResource extends Resource
{
    protected static ?string $model = OpeningBalanceMigration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $tenantRelationshipName = 'openingBalanceMigrations';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Opening Migration';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return OpeningBalanceMigrationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpeningBalanceMigrationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningBalanceMigrationsTable::configure($table);
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
            'index' => ListOpeningBalanceMigrations::route('/'),
            'view' => ViewOpeningBalanceMigration::route('/{record}'),
        ];
    }
}
