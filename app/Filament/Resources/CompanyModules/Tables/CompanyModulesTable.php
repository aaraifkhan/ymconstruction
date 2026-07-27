<?php

namespace App\Filament\Resources\CompanyModules\Tables;

use App\Enums\CompanyModuleState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('module_id')
            ->columns([
                TextColumn::make('module.name')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state')
                    ->badge()
                    ->formatStateUsing(fn (CompanyModuleState $state): string => $state->label())
                    ->color(fn (CompanyModuleState $state): string => match ($state) {
                        CompanyModuleState::Enabled => 'success',
                        CompanyModuleState::Disabled => 'danger',
                        CompanyModuleState::Inherit => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('variant')
                    ->placeholder('Default')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options(
                        collect(CompanyModuleState::cases())
                            ->mapWithKeys(fn (CompanyModuleState $state): array => [
                                $state->value => $state->label(),
                            ])
                            ->all()
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
