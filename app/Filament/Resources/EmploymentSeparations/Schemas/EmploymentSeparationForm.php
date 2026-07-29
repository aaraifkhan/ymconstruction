<?php

namespace App\Filament\Resources\EmploymentSeparations\Schemas;

use App\Enums\EmploymentSeparationType;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmploymentSeparationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resignation / termination')->schema([
                Select::make('employment_id')->relationship(
                    'employment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required(),
                Select::make('type')->options(collect(EmploymentSeparationType::cases())
                    ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all())
                    ->required(),
                DatePicker::make('request_date')->required(),
                DatePicker::make('proposed_last_working_date')->required(),
                TextInput::make('notice_days_required')->numeric()->minValue(0),
                TextInput::make('notice_days_served')->numeric()->minValue(0),
                Textarea::make('reason')->required()->columnSpanFull(),
                Textarea::make('authority')->helperText('Required for termination.')->columnSpanFull(),
                Textarea::make('protected_notes')->columnSpanFull(),
                Textarea::make('handover_notes')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
