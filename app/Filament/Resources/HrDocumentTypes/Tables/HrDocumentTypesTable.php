<?php

namespace App\Filament\Resources\HrDocumentTypes\Tables;

use App\Enums\HrDocumentApplicability;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class HrDocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->badge()->sortable(),
                TextColumn::make('applicability')->badge()->sortable(),
                TextColumn::make('default_classification')->label('Sensitivity')->badge(),
                IconColumn::make('requires_verification')->boolean(),
                IconColumn::make('requires_approval')->boolean(),
                IconColumn::make('is_required')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('applicability')->options(
                    collect(HrDocumentApplicability::cases())->mapWithKeys(
                        fn (HrDocumentApplicability $applicability): array => [
                            $applicability->value => $applicability->label(),
                        ],
                    )->all(),
                ),
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
