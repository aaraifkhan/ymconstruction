<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Tables;

use App\Actions\HR\TransitionEmployeeAssetCustodyAction;
use App\Enums\AssetCustodyExceptionType;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\EmployeeAssetCustody;
use App\Models\Employment;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeAssetCustodiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->placeholder('Draft')->searchable(),
                TextColumn::make('fixedAsset.asset_number')->label('Asset')->searchable(),
                TextColumn::make('fixedAsset.name')->label('Asset name')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('issued_on')->date()->sortable(),
                TextColumn::make('due_on')->date(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EmployeeAssetCustodyStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmployeeAssetCustody $record): bool => $record->status === EmployeeAssetCustodyStatus::Draft),
                Action::make('issue')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('issue', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => $record->status === EmployeeAssetCustodyStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (EmployeeAssetCustody $record) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->issue($record, auth()->user())),
                Action::make('acknowledge')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('acknowledge', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => $record->status === EmployeeAssetCustodyStatus::Issued)
                    ->requiresConfirmation()
                    ->action(fn (EmployeeAssetCustody $record) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->acknowledge($record, auth()->user())),
                Action::make('transfer')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('transfer', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => in_array($record->status, [
                        EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
                    ], true))
                    ->schema([
                        Select::make('employment_id')->label('New custodian')
                            ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                                ->with('employee')->get()->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                                ])->all())->searchable()->required(),
                        DatePicker::make('effective_on')->default(now())->required(),
                        TextInput::make('condition')->required(),
                        Textarea::make('reason'),
                    ])
                    ->action(fn (EmployeeAssetCustody $record, array $data) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->transfer(
                            $record,
                            Employment::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['employment_id']),
                            CarbonImmutable::parse($data['effective_on']),
                            $data['condition'],
                            $data['reason'] ?? null,
                            auth()->user(),
                        )),
                Action::make('requestReturn')->label('Request return')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('requestReturn', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => in_array($record->status, [
                        EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
                    ], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmployeeAssetCustody $record, array $data) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->requestReturn($record, $data['reason'], auth()->user())),
                Action::make('acceptReturn')->label('Accept return')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('acceptReturn', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => in_array($record->status, [
                        EmployeeAssetCustodyStatus::ReturnPending, EmployeeAssetCustodyStatus::Exception,
                    ], true))
                    ->schema([
                        DatePicker::make('returned_on')->default(now())->required(),
                        TextInput::make('condition')->required(),
                        Textarea::make('notes'),
                    ])
                    ->action(fn (EmployeeAssetCustody $record, array $data) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->acceptReturn(
                            $record,
                            CarbonImmutable::parse($data['returned_on']),
                            $data['condition'],
                            $data['notes'] ?? null,
                            auth()->user(),
                        )),
                Action::make('reportException')->label('Damage / loss')->color('danger')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('reportException', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => in_array($record->status, [
                        EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
                        EmployeeAssetCustodyStatus::ReturnPending,
                    ], true))
                    ->schema([
                        Select::make('type')->options(AssetCustodyExceptionType::class)->required(),
                        Textarea::make('notes')->required(),
                        TextInput::make('recommended_amount')->numeric()->minValue(0),
                        Textarea::make('recommendation_notes'),
                    ])
                    ->action(fn (EmployeeAssetCustody $record, array $data) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->reportException(
                            $record,
                            AssetCustodyExceptionType::from($data['type']),
                            $data['notes'],
                            filled($data['recommended_amount'] ?? null) ? (string) $data['recommended_amount'] : null,
                            $data['recommendation_notes'] ?? null,
                            auth()->user(),
                        )),
                Action::make('resolveException')->label('Resolve exception')
                    ->authorize(fn (EmployeeAssetCustody $record): bool => auth()->user()->can('resolveException', $record))
                    ->visible(fn (EmployeeAssetCustody $record): bool => $record->status === EmployeeAssetCustodyStatus::Exception)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmployeeAssetCustody $record, array $data) => app(TransitionEmployeeAssetCustodyAction::class)
                        ->resolveException($record, $data['reason'], auth()->user())),
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
