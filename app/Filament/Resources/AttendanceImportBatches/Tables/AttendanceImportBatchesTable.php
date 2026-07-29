<?php

namespace App\Filament\Resources\AttendanceImportBatches\Tables;

use App\Actions\HR\ReprocessAttendanceImportBatchAction;
use App\Enums\AttendanceImportSource;
use App\Models\AttendanceImportBatch;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceImportBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('attendanceDevice.name')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('original_filename')
                    ->searchable(),
                TextColumn::make('batch_checksum')
                    ->limit(12)
                    ->copyable(),
                TextColumn::make('row_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('accepted_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duplicate_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quarantined_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('initiatedBy.name')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
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
                Action::make('reprocess')
                    ->authorize(fn (AttendanceImportBatch $record): bool => auth()->user()?->can('reprocess', $record) ?? false)
                    ->visible(fn (AttendanceImportBatch $record): bool => $record->source === AttendanceImportSource::Csv)
                    ->requiresConfirmation()
                    ->action(function (AttendanceImportBatch $record): void {
                        $batch = app(ReprocessAttendanceImportBatchAction::class)->handle($record, auth()->user());
                        Notification::make()->title("Reprocess {$batch->status->value}")->success()->send();
                    }),
            ]);
    }
}
