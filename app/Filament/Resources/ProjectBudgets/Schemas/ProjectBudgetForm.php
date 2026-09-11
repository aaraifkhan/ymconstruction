<?php

namespace App\Filament\Resources\ProjectBudgets\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProjectBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Budget Version & Project Allocation')
                    ->description('Add cost-code lines after saving. Approval freezes this version and its lines.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('project_id')
                            ->label('Assigned Project')
                            ->relationship(
                                'project',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('version')
                            ->label('Budget Revision #')
                            ->required()
                            ->integer()
                            ->minValue(1),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->required()
                            ->length(3)
                            ->default('PKR')
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('notes')
                            ->label('Version Notes / Assumptions')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
