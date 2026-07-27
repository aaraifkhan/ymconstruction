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
            ->components([
                Section::make('Module details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('key')
                            ->helperText('Stable internal key. Do not change it after workflows start using the module.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Display order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Available for companies')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
