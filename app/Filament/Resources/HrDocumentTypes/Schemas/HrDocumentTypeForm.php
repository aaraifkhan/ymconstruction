<?php

namespace App\Filament\Resources\HrDocumentTypes\Schemas;

use App\Enums\DocumentClassification;
use App\Enums\HrDocumentApplicability;
use App\Enums\HrDocumentTypeCode;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class HrDocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('HR document type')
                ->schema([
                    Select::make('code')
                        ->options(collect(HrDocumentTypeCode::cases())->mapWithKeys(
                            fn (HrDocumentTypeCode $code): array => [$code->value => $code->label()],
                        )->all())
                        ->required()
                        ->disabledOn('edit')
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                'company_id',
                                Filament::getTenant()?->getKey(),
                            ),
                        ),
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('applicability')
                        ->options(collect(HrDocumentApplicability::cases())->mapWithKeys(
                            fn (HrDocumentApplicability $applicability): array => [
                                $applicability->value => $applicability->label(),
                            ],
                        )->all())
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('default_classification')
                        ->label('Default sensitivity')
                        ->options(collect(DocumentClassification::cases())->mapWithKeys(
                            fn (DocumentClassification $classification): array => [
                                $classification->value => $classification->label(),
                            ],
                        )->all())
                        ->required(),
                    Toggle::make('requires_issue_date')->label('Require issue date'),
                    Toggle::make('requires_expiry')->label('Require expiry date'),
                    Toggle::make('requires_verification')->label('Require verification'),
                    Toggle::make('requires_approval')->label('Require approval'),
                    Toggle::make('is_required')
                        ->label('Required for compliance')
                        ->helperText('Generic uploads remain available; workflows may block when a required type is missing.'),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
