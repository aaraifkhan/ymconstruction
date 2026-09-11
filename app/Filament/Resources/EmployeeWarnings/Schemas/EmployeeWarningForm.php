<?php

namespace App\Filament\Resources\EmployeeWarnings\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmployeeWarningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Disciplinary Warning Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employment_id')
                            ->label('Employee')
                            ->relationship(
                                'employment',
                                'employee_code',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warning_letter_template_id')
                            ->label('Warning Letter Template')
                            ->relationship(
                                'template',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload(),
                        TextInput::make('level')
                            ->label('Warning Level (e.g. 1st Verbal, 2nd Written, Final)')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('incident_date')
                            ->label('Date of Incident')
                            ->required(),
                        TextInput::make('subject')
                            ->label('Warning Subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Letter Contents / Description of Infraction')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
