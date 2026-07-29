<?php

namespace App\Filament\Resources\PerformanceAppraisals\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('performance_kpi_id')
                    ->relationship(
                        'kpi',
                        'name',
                        fn (Builder $query): Builder => $query
                            ->whereBelongsTo(Filament::getTenant())
                            ->where('is_active', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('goal')->required()->columnSpanFull(),
                TextInput::make('weight')->numeric()->minValue(0.0001)->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('goal')
            ->columns([
                TextColumn::make('kpi.name')->label('KPI')->searchable(),
                TextColumn::make('weight')->numeric(decimalPlaces: 2)->suffix('%'),
                TextColumn::make('score')->placeholder('Not reviewed'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    return [...$data, 'company_id' => Filament::getTenant()->getKey()];
                }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
