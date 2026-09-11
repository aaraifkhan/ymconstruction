<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('System Activity Log Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        TextInput::make('log_name')->label('Log Channel'),
                        TextInput::make('event')->label('Audit Event'),
                        TextInput::make('subject_type')->label('Subject Model'),
                        TextInput::make('subject_id')->label('Subject ID')->numeric(),
                        TextInput::make('causer_type')->label('Causer Type'),
                        TextInput::make('causer_id')->label('Causer ID')->numeric(),
                        Textarea::make('description')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('attribute_changes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('properties')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
