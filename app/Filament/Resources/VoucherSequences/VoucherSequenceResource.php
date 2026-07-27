<?php

namespace App\Filament\Resources\VoucherSequences;

use App\Filament\Resources\VoucherSequences\Pages\CreateVoucherSequence;
use App\Filament\Resources\VoucherSequences\Pages\EditVoucherSequence;
use App\Filament\Resources\VoucherSequences\Pages\ListVoucherSequences;
use App\Filament\Resources\VoucherSequences\Pages\ViewVoucherSequence;
use App\Filament\Resources\VoucherSequences\Schemas\VoucherSequenceForm;
use App\Filament\Resources\VoucherSequences\Schemas\VoucherSequenceInfolist;
use App\Filament\Resources\VoucherSequences\Tables\VoucherSequencesTable;
use App\Models\VoucherSequence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VoucherSequenceResource extends Resource
{
    protected static ?string $model = VoucherSequence::class;

    protected static ?string $tenantRelationshipName = 'voucherSequences';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNumberedList;

    public static function form(Schema $schema): Schema
    {
        return VoucherSequenceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VoucherSequenceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VoucherSequencesTable::configure($table);
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
            'index' => ListVoucherSequences::route('/'),
            'create' => CreateVoucherSequence::route('/create'),
            'view' => ViewVoucherSequence::route('/{record}'),
            'edit' => EditVoucherSequence::route('/{record}/edit'),
        ];
    }
}
