<?php

namespace App\Filament\Resources\EmploymentMovementRequests\Tables;

use App\Actions\HR\TransitionEmploymentMovementAction;
use App\Enums\EmploymentMovementStatus;
use App\Models\EmploymentMovementRequest;
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

class EmploymentMovementRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->placeholder('Draft')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('effective_on')->date()->sortable(),
                TextColumn::make('targetDepartment.name')->label('Department')->placeholder('—'),
                TextColumn::make('targetDesignation.name')->label('Designation')->placeholder('—'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmploymentMovementRequest $record): bool => in_array(
                    $record->status,
                    [EmploymentMovementStatus::Draft, EmploymentMovementStatus::Rejected],
                    true,
                )),
                Action::make('submit')
                    ->authorize(fn (EmploymentMovementRequest $record): bool => auth()->user()?->can('submit', $record) ?? false)
                    ->visible(fn (EmploymentMovementRequest $record): bool => in_array(
                        $record->status,
                        [EmploymentMovementStatus::Draft, EmploymentMovementStatus::Rejected],
                        true,
                    ))
                    ->requiresConfirmation()
                    ->action(fn (EmploymentMovementRequest $record) => app(TransitionEmploymentMovementAction::class)
                        ->submit($record, auth()->user())),
                Action::make('approve')
                    ->authorize(fn (EmploymentMovementRequest $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (EmploymentMovementRequest $record): bool => $record->status === EmploymentMovementStatus::PendingApproval)
                    ->requiresConfirmation()
                    ->action(fn (EmploymentMovementRequest $record) => app(TransitionEmploymentMovementAction::class)
                        ->approve($record, auth()->user())),
                Action::make('apply')
                    ->authorize(fn (EmploymentMovementRequest $record): bool => auth()->user()?->can('apply', $record) ?? false)
                    ->visible(fn (EmploymentMovementRequest $record): bool => $record->status === EmploymentMovementStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (EmploymentMovementRequest $record) => app(TransitionEmploymentMovementAction::class)
                        ->apply($record, auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (EmploymentMovementRequest $record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->visible(fn (EmploymentMovementRequest $record): bool => $record->status === EmploymentMovementStatus::PendingApproval)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmploymentMovementRequest $record, array $data) => app(TransitionEmploymentMovementAction::class)
                        ->reject($record, $data['reason'], auth()->user())),
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
