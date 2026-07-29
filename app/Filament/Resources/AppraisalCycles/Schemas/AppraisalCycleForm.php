<?php

namespace App\Filament\Resources\AppraisalCycles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppraisalCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appraisal cycle')->schema([
                TextInput::make('name')->required()->maxLength(255),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required()->afterOrEqual('starts_on'),
                TextInput::make('score_min')->numeric()->required(),
                TextInput::make('score_max')->numeric()->required(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
