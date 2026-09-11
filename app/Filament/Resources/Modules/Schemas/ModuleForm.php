<?php

namespace App\Filament\Resources\Modules\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('System Core Module Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Module Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('key')
                            ->label('Internal Key')
                            ->helperText('Stable identifier. Do not change.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Available for Companies')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Module Purpose & Functional Scope')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
