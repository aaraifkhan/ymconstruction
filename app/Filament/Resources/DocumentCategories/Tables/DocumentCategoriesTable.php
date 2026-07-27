<?php

namespace App\Filament\Resources\DocumentCategories\Tables;

use App\Enums\DocumentClassification;
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

class DocumentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('default_classification')
                    ->label('Sensitivity')
                    ->formatStateUsing(
                        fn (DocumentClassification $state): string => $state->label(),
                    )
                    ->badge(),
                IconColumn::make('requires_verification')
                    ->label('Verify')
                    ->boolean(),
                IconColumn::make('requires_approval')
                    ->label('Approve')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('default_classification')
                    ->label('Sensitivity')
                    ->options(
                        collect(DocumentClassification::cases())
                            ->mapWithKeys(fn (DocumentClassification $classification): array => [
                                $classification->value => $classification->label(),
                            ])
                            ->all(),
                    ),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active categories')
                    ->falseLabel('Inactive categories')
                    ->placeholder('All categories'),
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
