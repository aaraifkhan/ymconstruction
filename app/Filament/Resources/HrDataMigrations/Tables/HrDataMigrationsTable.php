<?php

namespace App\Filament\Resources\HrDataMigrations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HrDataMigrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('source_filename')->searchable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('row_count')->numeric(),
                TextColumn::make('valid_row_count')->numeric(),
                TextColumn::make('imported_row_count')->numeric(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
