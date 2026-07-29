<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Pages;

use App\Actions\HR\BuildAttendanceMonthlySummaryAction;
use App\Filament\Resources\AttendanceMonthlySummaries\AttendanceMonthlySummaryResource;
use App\Models\Employment;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceMonthlySummaries extends ListRecords
{
    protected static string $resource = AttendanceMonthlySummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate summary')
                ->schema([
                    Select::make('employment_id')
                        ->options(fn (): array => Employment::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->with('employee')
                            ->get()
                            ->mapWithKeys(fn (Employment $employment): array => [
                                $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                            ])->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('period_start')->required(),
                    DatePicker::make('period_end')->required()->afterOrEqual('period_start'),
                ])
                ->action(fn (array $data) => app(BuildAttendanceMonthlySummaryAction::class)->handle(
                    Employment::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['employment_id']),
                    CarbonImmutable::parse($data['period_start']),
                    CarbonImmutable::parse($data['period_end']),
                    auth()->user(),
                )),
        ];
    }
}
