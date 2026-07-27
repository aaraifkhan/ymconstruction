<?php

namespace App\Filament\Resources\JoiningLetters\Tables;

use App\Enums\JoiningLetterStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JoiningLettersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('letter_number')->label('Letter number')->searchable()->sortable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
                TextColumn::make('employment.designation.name')->label('Designation')->placeholder('—'),
                TextColumn::make('status')
                    ->formatStateUsing(fn (JoiningLetterStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (JoiningLetterStatus $state): string => $state->color()),
                TextColumn::make('letter_date')->date()->sortable(),
                TextColumn::make('issued_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(JoiningLetterStatus::cases())->mapWithKeys(
                    fn (JoiningLetterStatus $status): array => [$status->value => $status->label()],
                )->all()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }
}
