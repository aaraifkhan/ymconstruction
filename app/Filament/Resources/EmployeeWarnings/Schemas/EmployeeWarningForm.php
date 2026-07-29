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
        return $schema->components([
            Section::make('Warning letter')->schema([
                Select::make('employment_id')->relationship(
                    'employment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required(),
                Select::make('warning_letter_template_id')->relationship(
                    'template', 'name', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload(),
                TextInput::make('level')->required()->maxLength(100),
                DatePicker::make('incident_date')->required(),
                TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('body')->required()->rows(8)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
