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
        return $schema->components([
            Section::make('Budget version')
                ->description('Add cost-code lines after saving. Approval freezes this version and its lines.')
                ->schema([
                    Select::make('project_id')
                        ->relationship(
                            'project',
                            'name',
                            fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('version')
                        ->required()
                        ->integer()
                        ->minValue(1),
                    TextInput::make('currency_code')
                        ->required()
                        ->length(3)
                        ->default('PKR')
                        ->disabled()
                        ->dehydrated(),
                    Textarea::make('notes')->rows(3)->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
