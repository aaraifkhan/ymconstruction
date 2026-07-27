<?php

namespace App\Filament\Resources\ProjectBudgets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('cost_code')->required()->alphaDash()->maxLength(50),
            TextInput::make('description')->required()->maxLength(255),
            Select::make('cost_center_id')
                ->label('Cost center')
                ->relationship(
                    'costCenter',
                    'name',
                    fn (Builder $query): Builder => $query
                        ->where('company_id', $this->getOwnerRecord()->company_id)
                        ->where('is_active', true),
                )
                ->searchable()
                ->preload(),
            Select::make('item_category_id')
                ->label('Item category')
                ->relationship(
                    'itemCategory',
                    'name',
                    fn (Builder $query): Builder => $query
                        ->where('company_id', $this->getOwnerRecord()->company_id)
                        ->where('is_active', true),
                )
                ->searchable()
                ->preload(),
            TextInput::make('amount')
                ->required()
                ->numeric()
                ->minValue(0.0001)
                ->prefix($this->getOwnerRecord()->currency_code),
            TextInput::make('sort_order')->integer()->minValue(0)->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cost_code')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('cost_code')->badge()->searchable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('costCenter.name')->label('Cost center')->placeholder('-'),
                TextColumn::make('itemCategory.name')->label('Item category')->placeholder('-'),
                TextColumn::make('amount')->money(fn (): string => $this->getOwnerRecord()->currency_code)->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->mutateDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
                DeleteAction::make()->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
            ]);
    }
}
