<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Tables;

use App\Actions\HR\BuildAttendanceMonthlySummaryAction;
use App\Actions\HR\FinalizeAttendanceMonthlySummaryAction;
use App\Enums\AttendanceSummaryStatus;
use App\Models\AttendanceMonthlySummary;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceMonthlySummariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('employment.id')
                    ->searchable(),
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('scheduled_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('present_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('absent_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('half_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('leave_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unpaid_leave_units')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('source_checksum')
                    ->searchable(),
                TextColumn::make('finalizedBy.name')
                    ->searchable(),
                TextColumn::make('finalized_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (AttendanceMonthlySummary $record): bool => $record->status === AttendanceSummaryStatus::Draft),
                Action::make('rebuild')
                    ->authorize(fn (AttendanceMonthlySummary $record): bool => auth()->user()?->can('generate', $record) ?? false)
                    ->visible(fn (AttendanceMonthlySummary $record): bool => $record->status === AttendanceSummaryStatus::Draft)
                    ->action(fn (AttendanceMonthlySummary $record) => app(BuildAttendanceMonthlySummaryAction::class)
                        ->handle($record->employment, $record->period_start, $record->period_end, auth()->user())),
                Action::make('finalize')
                    ->authorize(fn (AttendanceMonthlySummary $record): bool => auth()->user()?->can('finalize', $record) ?? false)
                    ->visible(fn (AttendanceMonthlySummary $record): bool => $record->status === AttendanceSummaryStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (AttendanceMonthlySummary $record) => app(FinalizeAttendanceMonthlySummaryAction::class)
                        ->handle($record, auth()->user())),
            ]);
    }
}
