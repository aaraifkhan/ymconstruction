<?php

namespace App\Filament\Resources\JoiningLetters;

use App\Filament\Resources\JoiningLetters\Pages\CreateJoiningLetter;
use App\Filament\Resources\JoiningLetters\Pages\EditJoiningLetter;
use App\Filament\Resources\JoiningLetters\Pages\ListJoiningLetters;
use App\Filament\Resources\JoiningLetters\Pages\ViewJoiningLetter;
use App\Filament\Resources\JoiningLetters\Schemas\JoiningLetterForm;
use App\Filament\Resources\JoiningLetters\Schemas\JoiningLetterInfolist;
use App\Filament\Resources\JoiningLetters\Tables\JoiningLettersTable;
use App\Models\JoiningLetter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JoiningLetterResource extends Resource
{
    protected static ?string $model = JoiningLetter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'letter_number';

    protected static ?string $tenantRelationshipName = 'joiningLetters';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return JoiningLetterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JoiningLetterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JoiningLettersTable::configure($table);
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
            'index' => ListJoiningLetters::route('/'),
            'create' => CreateJoiningLetter::route('/create'),
            'view' => ViewJoiningLetter::route('/{record}'),
            'edit' => EditJoiningLetter::route('/{record}/edit'),
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
