<?php

namespace App\Filament\Resources\AttendanceRawEvents\Tables;

use App\Actions\HR\ReprocessAttendanceRawEventAction;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceRawEvent;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRawEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('attendanceDevice.name')
                    ->searchable(),
                TextColumn::make('attendance_import_batch_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attendance_device_user_mapping_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employment.id')
                    ->searchable(),
                TextColumn::make('external_user_id')
                    ->searchable(),
                TextColumn::make('original_punched_at_local')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('punched_at_utc')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_event_id')
                    ->searchable(),
                TextColumn::make('event_fingerprint')
                    ->searchable(),
                TextColumn::make('processing_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('received_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processed_at')
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
                    ->authorize(fn (AttendanceRawEvent $record): bool => auth()->user()?->can('reprocess', $record) ?? false)
                    ->visible(fn (AttendanceRawEvent $record): bool => $record->processing_status !== AttendanceRawEventStatus::Processed)
                    ->requiresConfirmation()
                    ->action(function (AttendanceRawEvent $record): void {
                        $event = app(ReprocessAttendanceRawEventAction::class)->handle($record, auth()->user());
                        Notification::make()->title("Event {$event->processing_status->value}")->success()->send();
                    }),
            ]);
    }
}
