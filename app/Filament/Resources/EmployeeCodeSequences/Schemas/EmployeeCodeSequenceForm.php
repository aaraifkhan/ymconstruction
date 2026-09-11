<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeCodeSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Automatic Employee Code Generation & Padding Format')
                    ->description('Existing codes never change. New employments use PREFIX plus the padded next number.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('prefix')
                            ->label('Code Prefix')
                            ->required()
                            ->alphaDash()
                            ->maxLength(20)
                            ->default('EMP'),
                        TextInput::make('padding')
                            ->label('Digit Padding Length')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(12)
                            ->default(5)
                            ->required(),
                        TextInput::make('next_number')
                            ->label('Next Sequence Number')
                            ->numeric()
                            ->default(1)
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->required(),
                    ]),
            ]);
    }
}
