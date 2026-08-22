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
            Section::make('Project Site & Store Details')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Site / Store Code')
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
                        ->label('Site / Store Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label('Location Type')
                        ->options(ProjectSiteType::class)
                        ->default(ProjectSiteType::Site->value)
                        ->required(),
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
                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->relationship(
                            'costCenter',
                            'name',
                            fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                    Textarea::make('location')
                        ->label('Physical Site / Yard Address')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
