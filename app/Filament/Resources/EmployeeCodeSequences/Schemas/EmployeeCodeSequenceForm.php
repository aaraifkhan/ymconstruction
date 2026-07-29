<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeCodeSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Automatic employee-code format')
                ->description('Existing codes never change. New employments use PREFIX plus the padded next number.')
                ->schema([
                    TextInput::make('prefix')
                        ->required()
                        ->alphaDash()
                        ->maxLength(20)
                        ->default('EMP'),
                    TextInput::make('padding')
                        ->numeric()
                        ->minValue(3)
                        ->maxValue(12)
                        ->default(5)
                        ->required(),
                    TextInput::make('next_number')
                        ->label('Next number')
                        ->numeric()
                        ->default(1)
                        ->disabledOn('edit')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create')
                        ->required(),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
