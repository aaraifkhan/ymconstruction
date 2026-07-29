<?php

namespace App\Filament\Resources\EmployeeWarnings\Tables;

use App\Actions\HR\TransitionEmployeeWarningAction;
use App\Enums\EmployeeWarningStatus;
use App\Models\EmployeeWarning;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeWarningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->placeholder('Draft')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee')->searchable(),
                TextColumn::make('incident_date')->date()->sortable(),
                TextColumn::make('level')->badge(),
                TextColumn::make('subject')->limit(45),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmployeeWarning $record): bool => $record->status === EmployeeWarningStatus::Draft),
                Action::make('issue')
                    ->authorize(fn (EmployeeWarning $record): bool => auth()->user()?->can('issue', $record) ?? false)
                    ->visible(fn (EmployeeWarning $record): bool => $record->status === EmployeeWarningStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (EmployeeWarning $record) => app(TransitionEmployeeWarningAction::class)
                        ->issue($record, auth()->user())),
                Action::make('respond')
                    ->authorize(fn (EmployeeWarning $record): bool => auth()->user()?->can('respond', $record) ?? false)
                    ->visible(fn (EmployeeWarning $record): bool => $record->status === EmployeeWarningStatus::Issued)
                    ->schema([Textarea::make('response')->required()])
                    ->action(fn (EmployeeWarning $record, array $data) => app(TransitionEmployeeWarningAction::class)
                        ->respond($record, $data['response'], auth()->user())),
                Action::make('acknowledge')
                    ->authorize(fn (EmployeeWarning $record): bool => auth()->user()?->can('acknowledge', $record) ?? false)
                    ->visible(fn (EmployeeWarning $record): bool => in_array(
                        $record->status,
                        [EmployeeWarningStatus::Issued, EmployeeWarningStatus::Responded],
                        true,
                    ))
                    ->requiresConfirmation()
                    ->action(fn (EmployeeWarning $record) => app(TransitionEmployeeWarningAction::class)
                        ->acknowledge($record, auth()->user())),
                Action::make('close')
                    ->authorize(fn (EmployeeWarning $record): bool => auth()->user()?->can('close', $record) ?? false)
                    ->visible(fn (EmployeeWarning $record): bool => $record->status === EmployeeWarningStatus::Acknowledged)
                    ->schema([Textarea::make('notes')->required()])
                    ->action(fn (EmployeeWarning $record, array $data) => app(TransitionEmployeeWarningAction::class)
                        ->close($record, $data['notes'], auth()->user())),
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
