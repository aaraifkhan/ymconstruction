<?php

namespace App\Filament\Resources\FinalSettlements\RelationManagers;

use App\Actions\HR\ManageFinalSettlementAction;
use App\Enums\FinalSettlementComponentType;
use App\Models\FinalSettlement;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('line_number'),
                TextColumn::make('component_type')->badge(),
                TextColumn::make('nature')->badge(),
                TextColumn::make('description'),
                TextColumn::make('source_reference')->label('Approved source'),
                TextColumn::make('amount')->money('PKR')
                    ->visible(fn (): bool => auth()->user()->can('viewAmounts', $this->getOwnerRecord())),
            ])
            ->headerActions([
                Action::make('addApprovedComponent')
                    ->authorize(fn (): bool => auth()->user()->can('update', $this->getOwnerRecord()))
                    ->visible(fn (): bool => $this->getOwnerRecord()->isEditable())
                    ->schema([
                        Select::make('component_type')
                            ->options(collect(FinalSettlementComponentType::cases())
                                ->reject->usesEmployeeAdvancesMapping()
                                ->mapWithKeys(fn (FinalSettlementComponentType $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required(),
                        TextInput::make('quantity')->numeric()->default(1)->minValue(0.0001)->required(),
                        TextInput::make('rate')->numeric()->minValue(0.0001)->required(),
                        TextInput::make('description')->required(),
                        TextInput::make('source_reference')->label('Approved source reference')->required(),
                        Textarea::make('evidence')->label('Approval / calculation evidence')->required(),
                    ])
                    ->action(function (array $data): void {
                        /** @var FinalSettlement $settlement */
                        $settlement = $this->getOwnerRecord();
                        app(ManageFinalSettlementAction::class)->addApprovedComponent(
                            $settlement,
                            FinalSettlementComponentType::from($data['component_type']),
                            (string) $data['quantity'],
                            (string) $data['rate'],
                            $data['description'],
                            $data['source_reference'],
                            ['approval_evidence' => $data['evidence']],
                            auth()->user(),
                        );
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
