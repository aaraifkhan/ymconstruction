<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('legal_name')
                    ->label('Legal name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parentCompany.name')
                    ->label('Parent company')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('child_companies_count')
                    ->label('Sub-companies')
                    ->counts('childCompanies')
                    ->badge()
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label('Users')
                    ->counts('members')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_company_id')
                    ->label('Parent company')
                    ->relationship('parentCompany', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active companies')
                    ->falseLabel('Inactive companies')
                    ->placeholder('All companies'),
                TrashedFilter::make(),
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
