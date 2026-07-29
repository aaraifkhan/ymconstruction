<?php

namespace App\Filament\Resources\Employments\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'changes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event_type')->badge(),
                TextEntry::make('effective_on')->date(),
                TextEntry::make('changed_fields')->listWithLineBreaks(),
                TextEntry::make('recordedBy.name')->label('Recorded by')->placeholder('System'),
                TextEntry::make('before_snapshot')->formatStateUsing(
                    fn (?array $state): string => $state === null
                        ? '—'
                        : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                )->columnSpanFull(),
                TextEntry::make('after_snapshot')->formatStateUsing(
                    fn (array $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                )->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('effective_on')
            ->columns([
                TextColumn::make('effective_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('event_type')->badge(),
                TextColumn::make('changed_fields')->label('Changed fields')->listWithLineBreaks()->limitList(4),
                TextColumn::make('recordedBy.name')->label('Recorded by')->placeholder('System'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
