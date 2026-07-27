<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Schemas;

use App\Models\JoiningLetterTemplate;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class JoiningLetterTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $placeholderHelp = collect(JoiningLetterTemplate::placeholderLabels())
            ->map(fn (string $label, string $placeholder): string => "{{ {$placeholder} }} — {$label}")
            ->implode("\n");

        return $schema->components([
            Section::make('Template details')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(100)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                'company_id',
                                Filament::getTenant()?->getKey(),
                            ),
                        ),
                    TextInput::make('subject_template')
                        ->label('Subject template')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('body_template')
                        ->label('Letter body template')
                        ->helperText("Allowed placeholders:\n{$placeholderHelp}")
                        ->rows(20)
                        ->required()
                        ->columnSpanFull(),
                    Toggle::make('is_default')->label('Default template'),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
