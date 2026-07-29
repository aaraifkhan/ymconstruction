<?php

namespace App\Filament\Resources\HrDataMigrations;

use App\Filament\Resources\HrDataMigrations\Pages\ListHrDataMigrations;
use App\Filament\Resources\HrDataMigrations\Pages\ViewHrDataMigration;
use App\Filament\Resources\HrDataMigrations\Schemas\HrDataMigrationForm;
use App\Filament\Resources\HrDataMigrations\Schemas\HrDataMigrationInfolist;
use App\Filament\Resources\HrDataMigrations\Tables\HrDataMigrationsTable;
use App\Models\HrDataMigration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HrDataMigrationResource extends Resource
{
    protected static ?string $model = HrDataMigration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'hrDataMigrations';

    protected static \UnitEnum|string|null $navigationGroup = 'HR';

    protected static ?string $navigationLabel = 'HR Data Migrations';

    protected static ?string $recordTitleAttribute = 'source_filename';

    public static function form(Schema $schema): Schema
    {
        return HrDataMigrationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HrDataMigrationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrDataMigrationsTable::configure($table);
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
            'index' => ListHrDataMigrations::route('/'),
            'view' => ViewHrDataMigration::route('/{record}'),
        ];
    }
}
