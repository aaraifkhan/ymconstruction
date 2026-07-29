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
            Section::make('Performance appraisal')->schema([
                Select::make('appraisal_cycle_id')->relationship(
                    'cycle', 'name', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required(),
                Select::make('employment_id')->relationship(
                    'employment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required(),
                Select::make('reviewer_employment_id')->relationship(
                    'reviewerEmployment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required()->different('employment_id'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
