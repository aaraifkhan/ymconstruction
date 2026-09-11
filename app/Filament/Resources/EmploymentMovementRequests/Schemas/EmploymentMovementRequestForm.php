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
        return $schema
            ->columns(1)
            ->components([
                Section::make('Promotion & Transfer Request Details')
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
                            ->label('Movement Type')
                            ->options(collect(EmploymentMovementType::cases())
                                ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all())
                            ->required(),
                        DatePicker::make('effective_on')
                            ->label('Effective Date')
                            ->required(),
                        Select::make('target_department_id')
                            ->label('Target Department')
                            ->relationship(
                                'targetDepartment',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('target_designation_id')
                            ->label('Target Designation')
                            ->relationship(
                                'targetDesignation',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('target_reporting_employment_id')
                            ->label('Target Reporting Manager')
                            ->relationship(
                                'targetReportingEmployment',
                                'employee_code',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('target_work_location_id')
                            ->label('Target Work Location')
                            ->relationship(
                                'targetWorkLocation',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('target_employment_category')
                            ->label('Target Category')
                            ->options(collect(EmploymentCategory::cases())
                                ->mapWithKeys(fn ($case): array => [$case->value => str($case->value)->headline()->toString()])->all()),
                        Textarea::make('reason')
                            ->label('Movement Justification / Business Case')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
