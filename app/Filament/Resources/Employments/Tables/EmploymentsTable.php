<?php

namespace App\Filament\Resources\Employments\Tables;

use App\Enums\EmploymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmploymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('employee_code')->label('Employee code')->searchable()->sortable(),
                TextColumn::make('designation.name')->placeholder('—')->sortable(),
                TextColumn::make('department.name')->placeholder('—')->sortable(),
                TextColumn::make('employment_category')
                    ->label('Category')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge(),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge(),
                TextColumn::make('joining_date')->date()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('employment_status')
                    ->options(collect(EmploymentStatus::cases())->mapWithKeys(
                        fn (EmploymentStatus $status): array => [$status->value => $status->label()],
                    )->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
