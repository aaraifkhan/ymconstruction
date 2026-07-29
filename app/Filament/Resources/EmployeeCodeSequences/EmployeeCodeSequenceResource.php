<?php

namespace App\Filament\Resources\EmployeeCodeSequences;

use App\Filament\Resources\EmployeeCodeSequences\Pages\CreateEmployeeCodeSequence;
use App\Filament\Resources\EmployeeCodeSequences\Pages\EditEmployeeCodeSequence;
use App\Filament\Resources\EmployeeCodeSequences\Pages\ListEmployeeCodeSequences;
use App\Filament\Resources\EmployeeCodeSequences\Pages\ViewEmployeeCodeSequence;
use App\Filament\Resources\EmployeeCodeSequences\Schemas\EmployeeCodeSequenceForm;
use App\Filament\Resources\EmployeeCodeSequences\Schemas\EmployeeCodeSequenceInfolist;
use App\Filament\Resources\EmployeeCodeSequences\Tables\EmployeeCodeSequencesTable;
use App\Models\EmployeeCodeSequence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmployeeCodeSequenceResource extends Resource
{
    protected static ?string $model = EmployeeCodeSequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?string $recordTitleAttribute = 'prefix';

    protected static ?string $tenantRelationshipName = 'employeeCodeSequences';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return EmployeeCodeSequenceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeCodeSequenceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeCodeSequencesTable::configure($table);
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
            'index' => ListEmployeeCodeSequences::route('/'),
            'create' => CreateEmployeeCodeSequence::route('/create'),
            'view' => ViewEmployeeCodeSequence::route('/{record}'),
            'edit' => EditEmployeeCodeSequence::route('/{record}/edit'),
        ];
    }
}
