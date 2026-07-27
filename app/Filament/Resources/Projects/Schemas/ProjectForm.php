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
            Section::make('Project details')
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
                    Select::make('client_party_id')
                        ->label('Client')
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
                        ->label('Consultant')
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
                    Textarea::make('location')->rows(2)->columnSpanFull(),
                    DatePicker::make('planned_start_date'),
                    DatePicker::make('planned_completion_date')->afterOrEqual('planned_start_date'),
                    DatePicker::make('actual_start_date'),
                    DatePicker::make('actual_completion_date')->afterOrEqual('actual_start_date'),
                    TextInput::make('contract_value')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('PKR'),
                    TextInput::make('currency_code')
                        ->required()
                        ->length(3)
                        ->default('PKR')
                        ->disabled()
                        ->dehydrated(),
                    Select::make('status')
                        ->options(ProjectStatus::class)
                        ->default(ProjectStatus::Planned->value)
                        ->required(),
                    KeyValue::make('retention_terms')
                        ->label('Retention terms')
                        ->helperText('Contract-specific terms only; do not enter an assumed statutory rate.')
                        ->columnSpanFull(),
                    KeyValue::make('mobilization_terms')
                        ->label('Mobilization terms')
                        ->helperText('Contract-specific recovery terms only.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
