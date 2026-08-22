<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\PartyRole;
use App\Enums\ProjectStatus;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project Identity & Parties')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Project Code')
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
                        ->label('Project Title')
                        ->required()
                        ->maxLength(255),
                    Select::make('status')
                        ->label('Project Status')
                        ->options(ProjectStatus::class)
                        ->default(ProjectStatus::Planned->value)
                        ->required(),
                    Select::make('client_party_id')
                        ->label('Client / Owner')
                        ->relationship(
                            'client',
                            'name',
                            fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->whereJsonContains('roles', PartyRole::Customer->value)
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('consultant_party_id')
                        ->label('Supervising Consultant')
                        ->relationship(
                            'consultant',
                            'name',
                            fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->whereJsonContains('roles', PartyRole::Consultant->value)
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload(),
                    Textarea::make('location')
                        ->label('Project Site Location / Address')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Schedule & Commercial Values')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('contract_value')
                        ->label('Total Contract Value (PKR)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('PKR'),
                    DatePicker::make('planned_start_date')
                        ->label('Planned Start Date'),
                    DatePicker::make('planned_completion_date')
                        ->label('Planned Completion Date')
                        ->afterOrEqual('planned_start_date'),
                    DatePicker::make('actual_start_date')
                        ->label('Actual Start Date'),
                    DatePicker::make('actual_completion_date')
                        ->label('Actual Completion Date')
                        ->afterOrEqual('actual_start_date'),
                    TextInput::make('currency_code')
                        ->label('Base Currency')
                        ->required()
                        ->length(3)
                        ->default('PKR')
                        ->disabled()
                        ->dehydrated(),
                ]),
            Section::make('Commercial & Retention Terms')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                ->columnSpanFull()
                ->schema([
                    KeyValue::make('retention_terms')
                        ->label('Retention Terms & Milestones')
                        ->helperText('Contract-specific terms only; do not enter an assumed statutory rate.')
                        ->columnSpanFull(),
                    KeyValue::make('mobilization_terms')
                        ->label('Mobilization Advance & Recovery Terms')
                        ->helperText('Contract-specific recovery terms only.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
