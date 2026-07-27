<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('company_employee_code')
                    ->label('Employee code')
                    ->state(fn (Employee $record): ?string => $record->employments->first()?->employee_code)
                    ->badge(),
                TextColumn::make('company_designation')
                    ->label('Designation')
                    ->state(fn (Employee $record): ?string => $record->employments->first()?->designation?->name)
                    ->placeholder('—'),
                TextColumn::make('company_department')
                    ->label('Department')
                    ->state(fn (Employee $record): ?string => $record->employments->first()?->department?->name)
                    ->placeholder('—'),
                IconColumn::make('is_active')->label('Active profile')->boolean()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
