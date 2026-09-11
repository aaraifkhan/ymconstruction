<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\ItemType;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Item & Material Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Item / SKU Code')
                            ->required()
                            ->alphaDash()
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        TextInput::make('name')
                            ->label('Item / Service Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Item Type')
                            ->options(ItemType::class)
                            ->default(ItemType::Material->value)
                            ->live()
                            ->required(),
                        Select::make('item_category_id')
                            ->label('Category')
                            ->relationship(
                                'category',
                                'name',
                                fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('unit_of_measure_id')
                            ->label('Unit of Measure (UOM)')
                            ->relationship(
                                'unitOfMeasure',
                                'name',
                                fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('default_tax_code_id')
                            ->label('Default Tax Code')
                            ->relationship(
                                'defaultTaxCode',
                                'name',
                                fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload(),
                        Toggle::make('track_inventory')
                            ->label('Track Inventory (Physical Stock)')
                            ->default(true)
                            ->disabled(fn (Get $get): bool => $get('type') === ItemType::Service->value)
                            ->dehydrated()
                            ->dehydrateStateUsing(fn (bool $state, Get $get): bool => $get('type') === ItemType::Service->value ? false : $state),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Technical Description / Specifications')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
