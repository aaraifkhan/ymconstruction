<?php

namespace App\Filament\Resources\EmployeeClearances\Tables;

use App\Actions\HR\ManageEmployeeClearanceAction;
use App\Enums\EmployeeClearanceStatus;
use App\Models\EmployeeClearance;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeClearancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable(),
                TextColumn::make('separation.type')->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('items_count')->counts('items')->label('Items'),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EmployeeClearanceStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('submit')
                    ->authorize(fn (EmployeeClearance $record): bool => auth()->user()->can('submit', $record))
                    ->visible(fn (EmployeeClearance $record): bool => $record->status === EmployeeClearanceStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (EmployeeClearance $record) => app(ManageEmployeeClearanceAction::class)
                        ->submit($record, auth()->user())),
                Action::make('refresh')
                    ->authorize(fn (EmployeeClearance $record): bool => auth()->user()->can('refresh', $record))
                    ->visible(fn (EmployeeClearance $record): bool => $record->status !== EmployeeClearanceStatus::Completed)
                    ->action(fn (EmployeeClearance $record) => app(ManageEmployeeClearanceAction::class)
                        ->refresh($record, auth()->user())),
                Action::make('complete')
                    ->authorize(fn (EmployeeClearance $record): bool => auth()->user()->can('complete', $record))
                    ->visible(fn (EmployeeClearance $record): bool => in_array($record->status, [
                        EmployeeClearanceStatus::InProgress, EmployeeClearanceStatus::Blocked,
                    ], true))
                    ->requiresConfirmation()
                    ->action(fn (EmployeeClearance $record) => app(ManageEmployeeClearanceAction::class)
                        ->complete($record, auth()->user())),
            ])
            ->toolbarActions([]);
    }
}
