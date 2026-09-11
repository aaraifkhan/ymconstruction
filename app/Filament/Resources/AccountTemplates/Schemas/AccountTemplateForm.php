<?php

namespace App\Filament\Resources\AccountTemplates\Schemas;

use App\Enums\AccountType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Chart of Account Template Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        TextInput::make('code')->label('Account Code')->required()->maxLength(50),
                        TextInput::make('name')->label('Account Name')->required(),
                        Select::make('parent_id')->label('Parent Account')->relationship('parent', 'name')->searchable()->preload(),
                        Select::make('account_type')->label('Account Type')->options(collect(AccountType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))->required(),
                        Select::make('normal_balance')->label('Normal Balance')->options(['debit' => 'Debit', 'credit' => 'Credit'])->required(),
                        TextInput::make('reporting_group')->label('Reporting Group')->required(),
                        TextInput::make('system_key')->label('System Key'),
                        Toggle::make('is_control_account')->label('Is Control Account'),
                        Toggle::make('allows_manual_posting')->label('Allows Manual Posting'),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
