<?php

namespace App\Filament\Resources\FinalSettlements\Tables;

use App\Actions\HR\ManageFinalSettlementAction;
use App\Actions\HR\PostFinalSettlementAction;
use App\Actions\HR\ReverseFinalSettlementAction;
use App\Enums\FinalSettlementStatus;
use App\Models\FinalSettlement;
use App\Reports\FinalSettlementReport;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinalSettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
                TextColumn::make('cutoff_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('net_amount')->money('PKR')
                    ->visible(fn (): bool => auth()->user()->hasRole('super_admin')
                        || auth()->user()->can('ViewAmounts:FinalSettlement')),
                TextColumn::make('balance_direction')->badge(),
                TextColumn::make('journalEntry.voucher_number')->label('GL voucher'),
            ])
            ->filters([
                SelectFilter::make('status')->options(FinalSettlementStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('submit')->authorize('submit')
                    ->visible(fn (FinalSettlement $record): bool => $record->isEditable())
                    ->requiresConfirmation()
                    ->action(fn (FinalSettlement $record) => app(ManageFinalSettlementAction::class)
                        ->submit($record, auth()->user())),
                Action::make('review')->authorize('review')
                    ->visible(fn (FinalSettlement $record): bool => $record->status === FinalSettlementStatus::Submitted)
                    ->requiresConfirmation()
                    ->action(fn (FinalSettlement $record) => app(ManageFinalSettlementAction::class)
                        ->review($record, auth()->user())),
                Action::make('approve')->authorize('approve')
                    ->visible(fn (FinalSettlement $record): bool => $record->status === FinalSettlementStatus::Reviewed)
                    ->requiresConfirmation()
                    ->action(fn (FinalSettlement $record) => app(ManageFinalSettlementAction::class)
                        ->approve($record, auth()->user())),
                Action::make('reject')->authorize('reject')
                    ->visible(fn (FinalSettlement $record): bool => in_array($record->status, [
                        FinalSettlementStatus::Submitted, FinalSettlementStatus::Reviewed,
                    ], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (FinalSettlement $record, array $data) => app(ManageFinalSettlementAction::class)
                        ->reject($record, auth()->user(), $data['reason'])),
                Action::make('post')->authorize('post')
                    ->visible(fn (FinalSettlement $record): bool => $record->status === FinalSettlementStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (FinalSettlement $record) => app(PostFinalSettlementAction::class)
                        ->handle($record, auth()->user())),
                Action::make('letter')->authorize('generateLetter')
                    ->visible(fn (FinalSettlement $record): bool => in_array($record->status, [
                        FinalSettlementStatus::Approved, FinalSettlementStatus::Posted,
                        FinalSettlementStatus::Settled,
                    ], true))
                    ->modalHeading('Final Settlement Letter')
                    ->modalContent(fn (FinalSettlement $record) => view(
                        'filament.final-settlements.letter',
                        ['letter' => app(FinalSettlementReport::class)->settlementLetter($record)],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('reverse')->authorize('reverse')
                    ->visible(fn (FinalSettlement $record): bool => in_array($record->status, [
                        FinalSettlementStatus::Posted, FinalSettlementStatus::Settled,
                    ], true))
                    ->schema([
                        DatePicker::make('reversal_date')->default(today())->required(),
                        Textarea::make('reason')->required(),
                    ])
                    ->action(fn (FinalSettlement $record, array $data) => app(ReverseFinalSettlementAction::class)
                        ->handle($record, auth()->user(), CarbonImmutable::parse($data['reversal_date']), $data['reason'])),
            ])
            ->toolbarActions([]);
    }
}
