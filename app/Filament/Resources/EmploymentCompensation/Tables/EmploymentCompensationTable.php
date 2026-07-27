<?php

namespace App\Filament\Resources\EmploymentCompensation\Tables;

use App\Enums\CompensationStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmploymentCompensationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employment.employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employment.employee_code')
                    ->label('Employee code')
                    ->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('effective_from')->date()->sortable(),
                TextColumn::make('effective_to')->date()->placeholder('Current')->sortable(),
                TextColumn::make('approved_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CompensationStatus::class),
                TrashedFilter::make(),
            ])
            ->defaultSort('effective_from', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
