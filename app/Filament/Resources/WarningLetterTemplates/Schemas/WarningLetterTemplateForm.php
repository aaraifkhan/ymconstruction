<?php

namespace App\Filament\Resources\WarningLetterTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarningLetterTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Warning Letter Template Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Template Code')
                            ->required()
                            ->alphaDash()
                            ->maxLength(50),
                        TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('level')
                            ->label('Warning Level')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('subject')
                            ->label('Default Subject Line')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        Toggle::make('requires_response')
                            ->label('Requires Employee Explanation/Response')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true),
                        Textarea::make('body')
                            ->label('Letter Body Template')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
