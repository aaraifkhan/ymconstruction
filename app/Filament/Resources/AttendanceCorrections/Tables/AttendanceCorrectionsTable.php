<?php

namespace App\Filament\Resources\AttendanceCorrections\Tables;

use App\Actions\HR\ApproveAttendanceCorrectionAction;
use App\Enums\AttendanceCorrectionStatus;
use App\Models\AttendanceCorrection;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceCorrectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('attendanceRecord.id')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('requestedBy.name')
                    ->searchable(),
                TextColumn::make('decidedBy.name')
                    ->searchable(),
                TextColumn::make('decided_at')
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
                EditAction::make()->visible(fn (AttendanceCorrection $record): bool => $record->status === AttendanceCorrectionStatus::Pending),
                Action::make('approve')
                    ->authorize(fn (AttendanceCorrection $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (AttendanceCorrection $record): bool => $record->status === AttendanceCorrectionStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn (AttendanceCorrection $record) => app(ApproveAttendanceCorrectionAction::class)
                        ->handle($record, auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (AttendanceCorrection $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (AttendanceCorrection $record): bool => $record->status === AttendanceCorrectionStatus::Pending)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (AttendanceCorrection $record, array $data) => app(ApproveAttendanceCorrectionAction::class)
                        ->handle($record, auth()->user(), false, $data['reason'])),
            ]);
    }
}
