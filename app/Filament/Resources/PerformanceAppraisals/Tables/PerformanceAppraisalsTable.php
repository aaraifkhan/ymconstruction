<?php

namespace App\Filament\Resources\PerformanceAppraisals\Tables;

use App\Actions\HR\TransitionPerformanceAppraisalAction;
use App\Enums\PerformanceAppraisalStatus;
use App\Models\PerformanceAppraisal;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PerformanceAppraisalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cycle.name')->searchable()->sortable(),
                TextColumn::make('employment.employee_code')->label('Employee')->searchable(),
                TextColumn::make('reviewerEmployment.employee_code')->label('Reviewer')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('overall_score')->label('Score')->placeholder('—'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PerformanceAppraisal $record): bool => in_array(
                    $record->status,
                    [PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected],
                    true,
                )),
                Action::make('submit')
                    ->authorize(fn (PerformanceAppraisal $record): bool => auth()->user()?->can('submit', $record) ?? false)
                    ->visible(fn (PerformanceAppraisal $record): bool => in_array(
                        $record->status,
                        [PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected],
                        true,
                    ))
                    ->requiresConfirmation()
                    ->action(fn (PerformanceAppraisal $record) => app(TransitionPerformanceAppraisalAction::class)
                        ->submit($record, auth()->user())),
                Action::make('review')
                    ->authorize(fn (PerformanceAppraisal $record): bool => auth()->user()?->can('review', $record) ?? false)
                    ->visible(fn (PerformanceAppraisal $record): bool => $record->status === PerformanceAppraisalStatus::Submitted)
                    ->schema(fn (PerformanceAppraisal $record): array => [
                        ...$record->items()->with('kpi')->get()->map(
                            fn ($item): TextInput => TextInput::make("scores.{$item->getKey()}.score")
                                ->label($item->kpi->name)
                                ->numeric()
                                ->required(),
                        )->all(),
                        Textarea::make('outcome')->required()->columnSpanFull(),
                    ])
                    ->action(fn (PerformanceAppraisal $record, array $data) => app(TransitionPerformanceAppraisalAction::class)
                        ->review($record, $data['scores'], $data['outcome'], auth()->user())),
                Action::make('approve')
                    ->authorize(fn (PerformanceAppraisal $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->visible(fn (PerformanceAppraisal $record): bool => $record->status === PerformanceAppraisalStatus::Reviewed)
                    ->requiresConfirmation()
                    ->action(fn (PerformanceAppraisal $record) => app(TransitionPerformanceAppraisalAction::class)
                        ->approve($record, auth()->user())),
                Action::make('acknowledge')
                    ->authorize(fn (PerformanceAppraisal $record): bool => auth()->user()?->can('acknowledge', $record) ?? false)
                    ->visible(fn (PerformanceAppraisal $record): bool => $record->status === PerformanceAppraisalStatus::Approved)
                    ->schema([Textarea::make('comments')->required()])
                    ->action(fn (PerformanceAppraisal $record, array $data) => app(TransitionPerformanceAppraisalAction::class)
                        ->acknowledge($record, $data['comments'], auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->authorize(fn (PerformanceAppraisal $record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->visible(fn (PerformanceAppraisal $record): bool => in_array(
                        $record->status,
                        [PerformanceAppraisalStatus::Submitted, PerformanceAppraisalStatus::Reviewed],
                        true,
                    ))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (PerformanceAppraisal $record, array $data) => app(TransitionPerformanceAppraisalAction::class)
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
