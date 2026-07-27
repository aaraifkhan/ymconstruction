<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Tables;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Filament\Resources\OpeningBalanceMigrations\Actions\OpeningMigrationWorkflowActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OpeningBalanceMigrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('opening_date')->date()->sortable(),
            TextColumn::make('source_filename')->searchable(),
            TextColumn::make('row_count')->numeric(),
            TextColumn::make('valid_row_count')->numeric(),
            TextColumn::make('source_debit_total')->money('PKR'),
            TextColumn::make('source_credit_total')->money('PKR'),
            TextColumn::make('status')->badge()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(OpeningBalanceMigrationStatus::class),
        ])->recordActions([
            OpeningMigrationWorkflowActions::validate(),
            OpeningMigrationWorkflowActions::import(),
            OpeningMigrationWorkflowActions::reverse(),
            ViewAction::make(),
        ])->defaultSort('opening_date', 'desc');
    }
}
