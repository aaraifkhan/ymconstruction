<?php

namespace App\Filament\Resources\DocumentCategories\Schemas;

use App\Enums\DocumentClassification;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DocumentCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Document Category Parameters')
                    ->description('Categories define the default sensitivity and review requirements for documents.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Category Code / Slug')
                            ->helperText('Stable identifier: e.g. company-registration.')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        Select::make('default_classification')
                            ->label('Default Security Sensitivity')
                            ->options(
                                collect(DocumentClassification::cases())
                                    ->mapWithKeys(fn (DocumentClassification $classification): array => [
                                        $classification->value => $classification->label(),
                                    ])
                                    ->all(),
                            )
                            ->default(DocumentClassification::Internal->value)
                            ->required(),
                        TextInput::make('retention_days')
                            ->label('Retention Period (Days)')
                            ->helperText('Leave empty until a retention policy is confirmed.')
                            ->numeric()
                            ->minValue(1),
                        Textarea::make('description')
                            ->label('Category Purpose / Guidelines')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Compliance & Verification Workflow')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('requires_expiry')
                            ->label('Expiry Date Required'),
                        Toggle::make('requires_verification')
                            ->label('Verification Required'),
                        Toggle::make('requires_approval')
                            ->label('Approval Required'),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
