<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceImportRowErrorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('attendance_import_batch_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('row_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_code')
                    ->searchable(),
                TextColumn::make('external_reference')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
