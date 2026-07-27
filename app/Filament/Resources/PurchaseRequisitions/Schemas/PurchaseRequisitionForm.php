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
        return $schema->components([
            Section::make('Requisition')
                ->columns(2)
                ->schema([
                    Select::make('project_id')
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
                        ->label('Project site / store')
                        ->options(fn (Get $get): array => ProjectSite::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->where('project_id', $get('project_id'))
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('required_date')->required()->minDate(today()),
                    TextInput::make('currency_code')->default('PKR')->length(3)->disabled()->dehydrated(),
                    Textarea::make('reason')->required()->maxLength(3000)->columnSpanFull(),
                ]),
            Section::make('Requested materials and services')
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
                                ->options(fn (): array => Item::query()
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->active()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Item $item): array => [$item->getKey() => "{$item->code} — {$item->name}"])
                                    ->all())
                                ->searchable()
                                ->required(),
                            Select::make('unit_of_measure_id')
                                ->label('UOM')
                                ->options(fn (): array => UnitOfMeasure::query()
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->active()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            Select::make('project_budget_line_id')
                                ->label('Approved budget line')
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
                                ->searchable(),
                            TextInput::make('quantity')->numeric()->minValue(0.0001)->required(),
                            TextInput::make('estimated_rate')->numeric()->minValue(0)->required(),
                            Textarea::make('specification')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
