<?php

namespace App\Filament\Resources\YearEndClosings\Pages;

use App\Actions\Accounting\PrepareYearEndClosingAction;
use App\Filament\Resources\YearEndClosings\YearEndClosingResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListYearEndClosings extends ListRecords
{
    protected static string $resource = YearEndClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prepare')->label('Prepare year-end closing')->authorize('create')
                ->schema([
                    Select::make('financial_year_id')->label('Financial year')
                        ->options(fn (): array => Filament::getTenant()?->financialYears()
                            ->whereDoesntHave('yearEndClosing')->orderByDesc('starts_on')->pluck('name', 'id')->all() ?? [])
                        ->required(),
                ])->action(fn (array $data) => app(PrepareYearEndClosingAction::class)->handle(
                    Filament::getTenant()->financialYears()->findOrFail($data['financial_year_id']),
                    Filament::auth()->user(),
                )),
        ];
    }
}
