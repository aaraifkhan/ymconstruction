<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Schemas;

use App\Enums\ClearanceArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClearanceChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Clearance Checklist Template Item')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        TextInput::make('code')->label('Checklist Code')->required()->maxLength(50),
                        TextInput::make('name')->label('Checklist Item Title')->required()->maxLength(255),
                        Select::make('area')->label('Clearance Department / Area')->options(ClearanceArea::class)->required(),
                        TextInput::make('sort_order')->label('Display Order')->integer()->minValue(0)->default(0)->required(),
                        Toggle::make('is_mandatory')->label('Is Mandatory')->default(true),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                        Textarea::make('description')->label('Item Description / Instructions')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
