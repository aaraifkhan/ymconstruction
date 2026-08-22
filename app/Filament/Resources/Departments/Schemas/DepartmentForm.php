<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Department Code')
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
                            ->label('Department Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        Select::make('parent_department_id')
                            ->label('Parent Department')
                            ->relationship(
                                'parentDepartment',
                                'name',
                                fn (Builder $query, ?Department $record): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->when($record !== null, fn (Builder $query): Builder => $query->whereKeyNot($record)),
                            )
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Department Mandate / Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
