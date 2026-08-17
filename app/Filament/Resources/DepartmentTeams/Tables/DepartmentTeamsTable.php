<?php

namespace App\Filament\Resources\DepartmentTeams\Tables;

use App\Enums\TeamType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DepartmentTeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Team Name')->searchable()->sortable(),
                TextColumn::make('code')->badge()->label('Code')->searchable(),
                TextColumn::make('department.name')->label('Department')->sortable()->searchable(),
                TextColumn::make('team_type')->badge()->label('Type')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('teamLead.employee.full_name')->label('Team Lead')->placeholder('—'),
                TextColumn::make('members_count')->label('Members')->counts('members'),
                TextColumn::make('tasks_count')->label('Tasks')->counts('tasks'),
                IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),
                SelectFilter::make('team_type')
                    ->options(collect(TeamType::cases())->mapWithKeys(fn (TeamType $type) => [$type->value => $type->label()]))
                    ->label('Team Type'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
