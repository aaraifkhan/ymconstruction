<?php

namespace App\Filament\Resources\AppraisalCycles\Tables;

use App\Actions\HR\TransitionAppraisalCycleAction;
use App\Enums\AppraisalCycleStatus;
use App\Models\AppraisalCycle;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AppraisalCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('starts_on')->date()->sortable(),
                TextColumn::make('ends_on')->date()->sortable(),
                TextColumn::make('score_min')->label('Min score'),
                TextColumn::make('score_max')->label('Max score'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->authorize(fn (AppraisalCycle $record): bool => auth()->user()?->can('activate', $record) ?? false)
                    ->visible(fn (AppraisalCycle $record): bool => $record->status === AppraisalCycleStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (AppraisalCycle $record) => app(TransitionAppraisalCycleAction::class)
                        ->activate($record, auth()->user())),
                Action::make('close')
                    ->authorize(fn (AppraisalCycle $record): bool => auth()->user()?->can('close', $record) ?? false)
                    ->visible(fn (AppraisalCycle $record): bool => $record->status === AppraisalCycleStatus::Active)
                    ->requiresConfirmation()
                    ->action(fn (AppraisalCycle $record) => app(TransitionAppraisalCycleAction::class)
                        ->close($record, auth()->user())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
