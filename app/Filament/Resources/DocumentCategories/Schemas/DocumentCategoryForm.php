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
            ->components([
                Section::make('Category details')
                    ->description('Categories define the default sensitivity and review requirements for documents.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Code')
                            ->helperText('Stable identifier, for example company-registration.')
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
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('default_classification')
                            ->label('Default sensitivity')
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
                            ->label('Retention period (days)')
                            ->helperText('Leave empty until a retention policy is confirmed.')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Workflow requirements')
                    ->schema([
                        Toggle::make('requires_expiry')
                            ->label('Expiry date required'),
                        Toggle::make('requires_verification')
                            ->label('Verification required'),
                        Toggle::make('requires_approval')
                            ->label('Approval required'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
