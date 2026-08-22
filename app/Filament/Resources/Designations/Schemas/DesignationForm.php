<?php

namespace App\Filament\Resources\Designations\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class DesignationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Designation Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Designation Code')
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
                            ->label('Designation Title')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        Select::make('department_id')
                            ->label('Department (Optional)')
                            ->relationship(
                                name: 'department',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave blank for company-wide designation.'),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Job Role Summary / Responsibilities')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
