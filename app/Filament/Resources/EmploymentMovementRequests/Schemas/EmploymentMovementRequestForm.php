<?php

namespace App\Filament\Resources\EmploymentMovementRequests\Schemas;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentMovementType;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmploymentMovementRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Promotion / transfer request')->schema([
                Select::make('employment_id')->relationship(
                    'employment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload()->required(),
                Select::make('type')->options(collect(EmploymentMovementType::cases())
                    ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all())
                    ->required(),
                DatePicker::make('effective_on')->required(),
                Select::make('target_department_id')->relationship(
                    'targetDepartment', 'name', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload(),
                Select::make('target_designation_id')->relationship(
                    'targetDesignation', 'name', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload(),
                Select::make('target_reporting_employment_id')->relationship(
                    'targetReportingEmployment', 'employee_code', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload(),
                Select::make('target_work_location_id')->relationship(
                    'targetWorkLocation', 'name', fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                )->searchable()->preload(),
                Select::make('target_employment_category')->options(collect(EmploymentCategory::cases())
                    ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all()),
                Textarea::make('reason')->required()->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
