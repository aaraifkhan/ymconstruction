<?php

namespace App\Filament\Resources\DocumentCategories\Schemas;

use App\Enums\DocumentClassification;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug')
                            ->label('Code')
                            ->badge(),
                        TextEntry::make('default_classification')
                            ->label('Default sensitivity')
                            ->formatStateUsing(
                                fn (DocumentClassification $state): string => $state->label(),
                            )
                            ->badge(),
                        TextEntry::make('retention_days')
                            ->label('Retention period')
                            ->suffix(' days')
                            ->placeholder('Not configured'),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Workflow requirements')
                    ->schema([
                        IconEntry::make('requires_expiry')->boolean(),
                        IconEntry::make('requires_verification')->boolean(),
                        IconEntry::make('requires_approval')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
