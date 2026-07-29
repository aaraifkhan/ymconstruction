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
        return $schema->components([
            Section::make('Warning template')->schema([
                TextInput::make('code')->required()->alphaDash()->maxLength(50),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('level')->required()->maxLength(100),
                TextInput::make('subject')->required()->maxLength(255),
                Textarea::make('body')->required()->rows(8)->columnSpanFull(),
                Toggle::make('requires_response')->default(false),
                Toggle::make('is_active')->default(true),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
