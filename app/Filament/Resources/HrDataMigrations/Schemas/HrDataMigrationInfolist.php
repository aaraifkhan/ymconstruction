<?php

namespace App\Filament\Resources\HrDataMigrations\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrDataMigrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Controlled source')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('company.name'),
                        TextEntry::make('type')->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('status')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('source_filename'),
                        TextEntry::make('source_checksum')->copyable(),
                        TextEntry::make('row_count')->numeric(),
                        TextEntry::make('valid_row_count')->numeric(),
                        TextEntry::make('imported_row_count')->numeric(),
                        TextEntry::make('prepared_by_id')->label('Prepared by user ID'),
                        TextEntry::make('validated_by_id')->label('Validated by user ID'),
                        TextEntry::make('imported_by_id')->label('Imported by user ID'),
                        TextEntry::make('rolled_back_by_id')->label('Rolled back by user ID'),
                        KeyValueEntry::make('source_totals')->columnSpanFull(),
                        KeyValueEntry::make('imported_totals')->columnSpanFull(),
                        KeyValueEntry::make('validation_summary')->columnSpanFull(),
                        TextEntry::make('rollback_reason')->columnSpanFull(),
                    ]),
                Section::make('Row evidence')
                    ->schema([
                        RepeatableEntry::make('rows')
                            ->schema([
                                TextEntry::make('source_row_number')->label('Row'),
                                TextEntry::make('source_key'),
                                TextEntry::make('row_checksum'),
                                KeyValueEntry::make('safe_row_data'),
                                KeyValueEntry::make('validation_errors'),
                                TextEntry::make('imported_record_type'),
                                TextEntry::make('imported_record_id'),
                            ])->columns(2),
                    ]),
            ]);
    }
}
