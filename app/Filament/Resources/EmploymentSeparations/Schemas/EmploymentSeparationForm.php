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
            Section::make('Resignation & Separation Details')
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
                    Select::make('type')
                        ->label('Separation Type')
                        ->options(collect(EmploymentSeparationType::cases())
                            ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all())
                        ->required(),
                    DatePicker::make('request_date')
                        ->label('Notice / Application Date')
                        ->required(),
                    DatePicker::make('proposed_last_working_date')
                        ->label('Proposed Last Working Day')
                        ->required(),
                    TextInput::make('notice_days_required')
                        ->label('Required Notice (Days)')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('notice_days_served')
                        ->label('Served Notice (Days)')
                        ->numeric()
                        ->minValue(0),
                    Textarea::make('reason')
                        ->label('Separation Reason')
                        ->rows(2)
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('authority')
                        ->label('Authorizing Authority / Board Resolution')
                        ->helperText('Required for involuntary termination.')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('handover_notes')
                        ->label('Departmental Handover Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('protected_notes')
                        ->label('Confidential HR Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
