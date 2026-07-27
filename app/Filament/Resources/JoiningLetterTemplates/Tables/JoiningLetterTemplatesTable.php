<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JoiningLetterTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('is_default', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->badge()->searchable(),
                IconColumn::make('is_default')->label('Default')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                TextColumn::make('joining_letters_count')->label('Letters')->counts('joiningLetters'),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }
}
