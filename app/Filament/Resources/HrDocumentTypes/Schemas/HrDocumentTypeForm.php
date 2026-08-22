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
            Section::make('HR Document Type & Compliance Parameters')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('code')
                        ->label('Document Type Code')
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
                    TextInput::make('name')
                        ->label('Type Display Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('applicability')
                        ->label('Applicability Scope')
                        ->options(collect(HrDocumentApplicability::cases())->mapWithKeys(
                            fn (HrDocumentApplicability $applicability): array => [
                                $applicability->value => $applicability->label(),
                            ],
                        )->all())
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('default_classification')
                        ->label('Default Security Sensitivity')
                        ->options(collect(DocumentClassification::cases())->mapWithKeys(
                            fn (DocumentClassification $classification): array => [
                                $classification->value => $classification->label(),
                            ],
                        )->all())
                        ->required(),
                    Toggle::make('requires_issue_date')->label('Require Issue Date'),
                    Toggle::make('requires_expiry')->label('Require Expiry Date'),
                    Toggle::make('requires_verification')->label('Require Verification'),
                    Toggle::make('requires_approval')->label('Require Approval'),
                    Toggle::make('is_required')
                        ->label('Mandatory for Compliance')
                        ->helperText('Workflows may block when a required type is missing.'),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                ]),
        ]);
    }
}
