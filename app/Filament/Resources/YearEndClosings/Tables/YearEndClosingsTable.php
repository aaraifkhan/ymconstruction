<?php

namespace App\Filament\Resources\YearEndClosings\Tables;

use App\Enums\YearEndClosingStatus;
use App\Filament\Resources\YearEndClosings\Actions\YearEndClosingWorkflowActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class YearEndClosingsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('financialYear.name')->label('Financial year')->sortable(),
            TextColumn::make('profit_or_loss')->money('PKR')->sortable(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('posted_at')->dateTime()->placeholder('—'),
            TextColumn::make('journalEntry.voucher_number')->label('Closing voucher')->placeholder('—'),
        ])->filters([
            SelectFilter::make('status')->options(YearEndClosingStatus::class),
        ])->recordActions([
            YearEndClosingWorkflowActions::approve(),
            YearEndClosingWorkflowActions::post(),
            YearEndClosingWorkflowActions::reverse(),
            ViewAction::make(),
        ]);
    }
}
