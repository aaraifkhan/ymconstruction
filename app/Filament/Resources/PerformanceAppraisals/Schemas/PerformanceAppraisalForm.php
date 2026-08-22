<?php

namespace App\Filament\Resources\PerformanceAppraisals\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PerformanceAppraisalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appraisal Review Setup')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('appraisal_cycle_id')
                        ->label('Appraisal Cycle')
                        ->relationship(
                            'cycle',
                            'name',
                            fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('employment_id')
                        ->label('Employee to Review')
                        ->relationship(
                            'employment',
                            'employee_code',
                            fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('reviewer_employment_id')
                        ->label('Appraising Manager / Reviewer')
                        ->relationship(
                            'reviewerEmployment',
                            'employee_code',
                            fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->different('employment_id'),
                ]),
        ]);
    }
}
