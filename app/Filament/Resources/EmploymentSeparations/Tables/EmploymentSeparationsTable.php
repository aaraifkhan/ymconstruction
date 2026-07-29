<?php

namespace App\Filament\Resources\EmploymentSeparations\Tables;

use App\Actions\HR\ManageEmployeeClearanceAction;
use App\Actions\HR\ManageFinalSettlementAction;
use App\Actions\HR\TransitionEmploymentSeparationAction;
use App\Enums\EmployeeClearanceStatus;
use App\Enums\EmploymentAccessReviewStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\EmploymentSeparationType;
use App\Models\EmployeeClearance;
use App\Models\EmploymentSeparation;
use App\Models\FinalSettlement;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmploymentSeparationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->placeholder('Draft')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('request_date')->date()->sortable(),
                TextColumn::make('proposed_last_working_date')->label('Proposed last day')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('access_review_status')->label('Access review')->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmploymentSeparation $record): bool => in_array(
                    $record->status,
                    [EmploymentSeparationStatus::Draft, EmploymentSeparationStatus::Rejected],
                    true,
                )),
                Action::make('submit')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('submit', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => in_array(
                        $record->status,
                        [EmploymentSeparationStatus::Draft, EmploymentSeparationStatus::Rejected],
                        true,
                    ))
                    ->requiresConfirmation()
                    ->action(fn (EmploymentSeparation $record) => app(TransitionEmploymentSeparationAction::class)
                        ->submit($record, auth()->user())),
                Action::make('accept')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('accept', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => $record->type === EmploymentSeparationType::Resignation
                        && $record->status === EmploymentSeparationStatus::Submitted)
                    ->requiresConfirmation()
                    ->action(fn (EmploymentSeparation $record) => app(TransitionEmploymentSeparationAction::class)
                        ->acceptResignation($record, auth()->user())),
                Action::make('approve')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => in_array(
                        $record->status,
                        [EmploymentSeparationStatus::Submitted, EmploymentSeparationStatus::Accepted],
                        true,
                    ))
                    ->schema([
                        DatePicker::make('last_working_date')
                            ->default(fn (EmploymentSeparation $record) => $record->proposed_last_working_date)
                            ->required(),
                    ])
                    ->action(fn (EmploymentSeparation $record, array $data) => app(TransitionEmploymentSeparationAction::class)
                        ->approve($record, CarbonImmutable::parse($data['last_working_date']), auth()->user())),
                Action::make('withdraw')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('withdraw', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => $record->type === EmploymentSeparationType::Resignation
                        && in_array($record->status, [EmploymentSeparationStatus::Submitted, EmploymentSeparationStatus::Accepted], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmploymentSeparation $record, array $data) => app(TransitionEmploymentSeparationAction::class)
                        ->withdraw($record, $data['reason'], auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => in_array(
                        $record->status,
                        [EmploymentSeparationStatus::Submitted, EmploymentSeparationStatus::Accepted],
                        true,
                    ))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmploymentSeparation $record, array $data) => app(TransitionEmploymentSeparationAction::class)
                        ->reject($record, $data['reason'], auth()->user())),
                Action::make('completeAccessReview')
                    ->label('Complete access review')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can('reviewAccess', $record) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => $record->access_review_status === EmploymentAccessReviewStatus::Pending
                        && $record->status === EmploymentSeparationStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (EmploymentSeparation $record) => app(TransitionEmploymentSeparationAction::class)
                        ->completeAccessReview($record, auth()->user())),
                Action::make('prepareClearance')
                    ->label('Prepare clearance')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can(
                        'prepare',
                        [EmployeeClearance::class, $record],
                    ) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => $record->status === EmploymentSeparationStatus::Approved
                        && ! $record->clearance()->exists())
                    ->requiresConfirmation()
                    ->action(fn (EmploymentSeparation $record) => app(ManageEmployeeClearanceAction::class)
                        ->prepare($record, auth()->user())),
                Action::make('prepareFinalSettlement')
                    ->label('Prepare Final Settlement')
                    ->authorize(fn (EmploymentSeparation $record): bool => auth()->user()?->can(
                        'prepare',
                        [FinalSettlement::class, $record],
                    ) ?? false)
                    ->visible(fn (EmploymentSeparation $record): bool => $record->status === EmploymentSeparationStatus::Approved
                        && $record->clearance?->status === EmployeeClearanceStatus::Completed
                        && ! $record->finalSettlement()->exists())
                    ->requiresConfirmation()
                    ->action(fn (EmploymentSeparation $record) => app(ManageFinalSettlementAction::class)
                        ->prepare($record, auth()->user())),
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
