<?php

namespace App\Filament\Resources\CompanyModules\Schemas;

use App\Enums\CompanyModuleState;
use Filament\Facades\Filament;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CompanyModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company module configuration')
                    ->schema([
                        Select::make('module_id')
                            ->label('Module')
                            ->relationship(
                                name: 'module',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('sort_order'),
                            )
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit')
                            ->required(),
                        Select::make('state')
                            ->options(
                                collect(CompanyModuleState::cases())
                                    ->mapWithKeys(fn (CompanyModuleState $state): array => [
                                        $state->value => $state->label(),
                                    ])
                                    ->all()
                            )
                            ->default(CompanyModuleState::Inherit->value)
                            ->required(),
                        TextInput::make('variant')
                            ->helperText('Leave blank unless a confirmed company workflow requires a named variant.')
                            ->maxLength(100),
                        KeyValue::make('settings')
                            ->helperText('Only use confirmed module settings. Avoid storing core structured business data here.')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
