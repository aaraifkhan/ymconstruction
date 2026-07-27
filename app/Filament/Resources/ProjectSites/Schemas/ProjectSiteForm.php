<?php

namespace App\Filament\Resources\ProjectSites\Schemas;

use App\Enums\ProjectSiteType;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class ProjectSiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project site or store')
                ->schema([
                    TextInput::make('code')
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
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('project_id')
                        ->relationship(
                            'project',
                            'name',
                            fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('cost_center_id')
                        ->label('Cost center')
                        ->relationship(
                            'costCenter',
                            'name',
                            fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                    Select::make('type')
                        ->options(ProjectSiteType::class)
                        ->default(ProjectSiteType::Site->value)
                        ->required(),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                    Textarea::make('location')->rows(3)->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
