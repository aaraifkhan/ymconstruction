<?php

namespace App\Filament\Resources\AttendanceRecords\Tables;

use App\Actions\HR\FinalizeAttendanceRecordAction;
use App\Enums\AttendanceRecordState;
use App\Models\AttendanceRecord;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('employment.id')
                    ->searchable(),
                TextColumn::make('shiftAssignment.id')
                    ->searchable(),
                TextColumn::make('attendanceRule.name')
                    ->searchable(),
                TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('day_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('state')
                    ->badge()
                    ->searchable(),
                TextColumn::make('first_in_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_out_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('worked_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_minutes')
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
                EditAction::make()->visible(fn (AttendanceRecord $record): bool => $record->state === AttendanceRecordState::Draft),
                Action::make('finalize')
                    ->authorize(fn (AttendanceRecord $record): bool => auth()->user()?->can('finalize', $record) ?? false)
                    ->visible(fn (AttendanceRecord $record): bool => $record->state === AttendanceRecordState::Draft)
                    ->requiresConfirmation()
                    ->action(fn (AttendanceRecord $record) => app(FinalizeAttendanceRecordAction::class)
                        ->handle($record, auth()->user())),
            ]);
    }
}
