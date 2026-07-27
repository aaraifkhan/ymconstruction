<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Pages;

use App\Actions\Accounting\PrepareOpeningBalanceMigrationAction;
use App\Filament\Resources\OpeningBalanceMigrations\OpeningBalanceMigrationResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ListOpeningBalanceMigrations extends ListRecords
{
    protected static string $resource = OpeningBalanceMigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dryRun')->label('Upload CSV dry run')->authorize('create')
                ->schema([
                    Select::make('financial_period_id')->label('Opening period')
                        ->options(fn (): array => Filament::getTenant()?->financialPeriods()
                            ->with('financialYear')->orderBy('starts_on')->get()
                            ->mapWithKeys(fn ($period): array => [$period->getKey() => "{$period->financialYear->name} — {$period->name}"])->all() ?? [])
                        ->required(),
                    DatePicker::make('opening_date')->required(),
                    FileUpload::make('source_file')->disk('local')
                        ->directory(fn (): string => 'opening-migrations/'.Filament::getTenant()->getKey().'/source')
                        ->visibility('private')->storeFileNamesIn('original_filename')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])->rules(['extensions:csv'])
                        ->maxSize(10240)->required(),
                ])->action(function (array $data): void {
                    $path = (string) Arr::get($data, 'source_file');
                    $migration = app(PrepareOpeningBalanceMigrationAction::class)->handle(
                        Filament::getTenant()->financialPeriods()->findOrFail($data['financial_period_id']),
                        Filament::auth()->user(),
                        Carbon::parse($data['opening_date']),
                        (string) Arr::get($data, 'original_filename', basename($path)),
                        Storage::disk('local')->get($path),
                    );
                    $migration->update(['source_path' => $path]);
                }),
        ];
    }
}
