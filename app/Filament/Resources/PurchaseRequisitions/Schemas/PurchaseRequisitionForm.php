<?php

namespace App\Filament\Resources\PurchaseRequisitions\Schemas;

use App\Enums\ProjectBudgetStatus;
use App\Models\Item;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectSite;
use App\Models\UnitOfMeasure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequisitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Requisition Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship(
                                'project',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->live()
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('project_site_id')
                            ->label('Project Site / Store')
                            ->options(fn (Get $get): array => ProjectSite::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('project_id', $get('project_id'))
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        DatePicker::make('required_date')->label('Required Date')->required()->minDate(today()),
                        TextInput::make('currency_code')->label('Currency')->default('PKR')->length(3)->disabled()->dehydrated(),
                        Textarea::make('reason')->label('Reason for Requisition')->required()->maxLength(3000)->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Requested Materials and Services')
                    ->columnSpanFull()
                    ->description('Budget reference is optional, but a linked line must belong to the current approved project budget.')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship()
                            ->orderColumn('line_number')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'company_id' => Filament::getTenant()->getKey(),
                            ])
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item / Material')
                                    ->options(fn (): array => Item::query()
                                        ->whereBelongsTo(Filament::getTenant())
                                        ->active()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Item $item): array => [$item->getKey() => "{$item->code} — {$item->name}"])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('unit_of_measure_id')
                                    ->label('Unit of Measure (UOM)')
                                    ->options(fn (): array => UnitOfMeasure::query()
                                        ->whereBelongsTo(Filament::getTenant())
                                        ->active()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                Select::make('project_budget_line_id')
                                    ->label('Approved Budget Line (Optional)')
                                    ->options(fn (Get $get): array => ProjectBudgetLine::query()
                                        ->whereBelongsTo(Filament::getTenant())
                                        ->whereHas('budget', fn (Builder $query): Builder => $query
                                            ->where('project_id', $get('../../project_id'))
                                            ->where('status', ProjectBudgetStatus::Approved))
                                        ->orderBy('cost_code')
                                        ->get()
                                        ->mapWithKeys(fn (ProjectBudgetLine $line): array => [
                                            $line->getKey() => "{$line->cost_code} — {$line->description}",
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->required(),
                                TextInput::make('estimated_rate')
                                    ->label('Est. Unit Rate (PKR)')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->minValue(0)
                                    ->required(),
                                Textarea::make('specification')
                                    ->label('Specification & Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
