<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Schemas;

use App\Enums\ClearanceArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClearanceChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->required()->maxLength(50),
                TextInput::make('name')->required()->maxLength(255),
                Select::make('area')->options(ClearanceArea::class)->required(),
                Textarea::make('description')->columnSpanFull(),
                Toggle::make('is_mandatory')->default(true),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->integer()->minValue(0)->default(0)->required(),
            ]);
    }
}
