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
            Section::make('Template Details & Body Content')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Template Code')
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
                    Toggle::make('is_default')
                        ->label('Is Company Default Template'),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                    TextInput::make('subject_template')
                        ->label('Subject Template')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('body_template')
                        ->label('Letter Body Template')
                        ->helperText("Allowed placeholders:\n{$placeholderHelp}")
                        ->rows(14)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
