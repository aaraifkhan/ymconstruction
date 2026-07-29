<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\Actions\DocumentFileActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('hrDocumentType.name')
                    ->label('HR type')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('classification')
                    ->label('Sensitivity')
                    ->formatStateUsing(
                        fn (DocumentClassification $state): string => $state->label(),
                    )
                    ->badge(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (DocumentStatus $state): string => $state->color()),
                TextColumn::make('currentVersion.version')
                    ->label('Version')
                    ->prefix('v'),
                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('document_category_id')
                    ->label('Category')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->whereBelongsTo(Filament::getTenant()),
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('hr_document_type_id')
                    ->label('HR document type')
                    ->relationship(
                        name: 'hrDocumentType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->whereBelongsTo(Filament::getTenant()),
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('classification')
                    ->label('Sensitivity')
                    ->options(
                        collect(DocumentClassification::cases())
                            ->mapWithKeys(fn (DocumentClassification $classification): array => [
                                $classification->value => $classification->label(),
                            ])
                            ->all(),
                    ),
                SelectFilter::make('status')
                    ->options(
                        collect(DocumentStatus::cases())
                            ->mapWithKeys(fn (DocumentStatus $status): array => [
                                $status->value => $status->label(),
                            ])
                            ->all(),
                    ),
                Filter::make('expired')
                    ->label('Expired documents')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->whereNotNull('expiry_date')
                            ->whereDate('expiry_date', '<', today()),
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DocumentFileActions::previewCurrent(),
                DocumentFileActions::downloadCurrent(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
