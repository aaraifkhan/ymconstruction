<?php

namespace App\Filament\Resources\YearEndClosings;

use App\Filament\Resources\YearEndClosings\Pages\ListYearEndClosings;
use App\Filament\Resources\YearEndClosings\Pages\ViewYearEndClosing;
use App\Filament\Resources\YearEndClosings\Schemas\YearEndClosingForm;
use App\Filament\Resources\YearEndClosings\Schemas\YearEndClosingInfolist;
use App\Filament\Resources\YearEndClosings\Tables\YearEndClosingsTable;
use App\Models\YearEndClosing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class YearEndClosingResource extends Resource
{
    protected static ?string $model = YearEndClosing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $tenantRelationshipName = 'yearEndClosings';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Year-end Closings';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return YearEndClosingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return YearEndClosingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YearEndClosingsTable::configure($table);
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
            'index' => ListYearEndClosings::route('/'),
            'view' => ViewYearEndClosing::route('/{record}'),
        ];
    }
}
