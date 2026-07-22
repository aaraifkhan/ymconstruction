<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('causer.name')
                                ->label('Performed By')
                                ->default('System'),
                            TextEntry::make('event')
                                ->label('Action')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'created' => 'success',
                                    'updated' => 'warning',
                                    'deleted' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('subject_type')
                                ->label('Target Model')
                                ->formatStateUsing(fn ($state) => class_basename($state))
                                ->default('General Settings'),
                            TextEntry::make('subject.name')
                                ->label('Target Name')
                                ->default('Application Configurations'),
                        ]),
                        TextEntry::make('description')
                            ->label('Log Message')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Changes')
                    ->schema([
                        Grid::make(2)->schema([
                            KeyValueEntry::make('old_values')
                                ->label('Old Values')
                                ->state(fn ($record) => $record->attribute_changes?->get('old') ?? $record->properties?->get('old') ?? [])
                                ->placeholder('No previous values'),
                            KeyValueEntry::make('new_values')
                                ->label('New Values')
                                ->state(fn ($record) => $record->attribute_changes?->get('attributes') ?? $record->properties?->get('attributes') ?? [])
                                ->placeholder('No new values'),
                        ]),
                    ])->columnSpanFull(),
                    
                Section::make('Metadata')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Logged At')
                                ->dateTime(),
                        ]),
                    ])
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
