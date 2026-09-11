<?php

namespace App\Filament\Resources\DepartmentTeams\Schemas;

use App\Enums\TeamType;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DepartmentTeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Department Team Information')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship(
                                'department',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Team Name')
                            ->placeholder('e.g. Social Media Team, Graphic Design')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Team Code')
                            ->placeholder('e.g. SMM, GFX, WEB')
                            ->maxLength(50),
                        Select::make('team_type')
                            ->label('Specialized Team Type')
                            ->options(collect(TeamType::cases())->mapWithKeys(fn (TeamType $type) => [$type->value => $type->label()]))
                            ->default(TeamType::Other->value)
                            ->required(),
                        Select::make('team_lead_id')
                            ->label('Team Lead / Manager')
                            ->relationship(
                                'teamLead',
                                'employee_code',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->getOptionLabelFromRecordUsing(fn (Employment $record) => "{$record->employee?->full_name} ({$record->employee_code})")
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Team Description / Scope')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
