<?php

namespace App\Filament\Resources\AttendancePunches\Tables;

use App\Actions\HR\ApproveAttendancePunchAction;
use App\Enums\AttendancePunchStatus;
use App\Models\AttendancePunch;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancePunchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('employment.id')
                    ->searchable(),
                TextColumn::make('punched_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('createdBy.name')
                    ->searchable(),
                TextColumn::make('approvedBy.name')
                    ->searchable(),
                TextColumn::make('approved_at')
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
                EditAction::make()->visible(fn (AttendancePunch $record): bool => $record->status === AttendancePunchStatus::Pending),
                Action::make('approve')
                    ->authorize(fn (AttendancePunch $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (AttendancePunch $record): bool => $record->status === AttendancePunchStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn (AttendancePunch $record) => app(ApproveAttendancePunchAction::class)
                        ->handle($record, auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (AttendancePunch $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (AttendancePunch $record): bool => $record->status === AttendancePunchStatus::Pending)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (AttendancePunch $record, array $data) => app(ApproveAttendancePunchAction::class)
                        ->handle($record, auth()->user(), false, $data['reason'])),
            ]);
    }
}
