<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Actions\HR\ApproveLeaveRequestAction;
use App\Actions\HR\CancelLeaveRequestAction;
use App\Actions\HR\ManagerApproveLeaveRequestAction;
use App\Actions\HR\RejectLeaveRequestAction;
use App\Actions\HR\SubmitLeaveRequestAction;
use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('employment.id')
                    ->searchable(),
                TextColumn::make('leaveType.name')
                    ->searchable(),
                TextColumn::make('leavePolicy.name')
                    ->searchable(),
                TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('requested_units')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_paid_snapshot')
                    ->boolean(),
                TextColumn::make('payroll_impact_snapshot')
                    ->badge()
                    ->searchable(),
                TextColumn::make('requestedBy.name')
                    ->searchable(),
                TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('managerDecidedBy.name')
                    ->searchable(),
                TextColumn::make('manager_decided_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hrDecidedBy.name')
                    ->searchable(),
                TextColumn::make('hr_decided_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelledBy.name')
                    ->searchable(),
                TextColumn::make('cancelled_at')
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
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (LeaveRequest $record): bool => $record->status === LeaveRequestStatus::Draft),
                Action::make('submit')
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()?->can('submit', $record) ?? false)
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveRequestStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => app(SubmitLeaveRequestAction::class)->handle($record, auth()->user())),
                Action::make('managerApprove')
                    ->label('Manager approve')
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()?->can('managerApprove', $record) ?? false)
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveRequestStatus::Requested)
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => app(ManagerApproveLeaveRequestAction::class)->handle($record, auth()->user())),
                Action::make('approve')
                    ->label('HR approve')
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveRequestStatus::ManagerApproved)
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => app(ApproveLeaveRequestAction::class)->handle($record, auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->visible(fn (LeaveRequest $record): bool => in_array($record->status, [LeaveRequestStatus::Requested, LeaveRequestStatus::ManagerApproved], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (LeaveRequest $record, array $data) => app(RejectLeaveRequestAction::class)
                        ->handle($record, auth()->user(), $data['reason'])),
                Action::make('cancel')
                    ->color('danger')
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()?->can('cancel', $record) ?? false)
                    ->visible(fn (LeaveRequest $record): bool => in_array($record->status, [LeaveRequestStatus::Requested, LeaveRequestStatus::ManagerApproved, LeaveRequestStatus::Approved], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (LeaveRequest $record, array $data) => app(CancelLeaveRequestAction::class)
                        ->handle($record, auth()->user(), $data['reason'])),
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
