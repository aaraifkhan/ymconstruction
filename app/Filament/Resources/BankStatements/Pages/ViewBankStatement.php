<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Actions\Banking\ImportBankStatementAction;
use App\Enums\BankStatementStatus;
use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\BankStatement;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Arr;

class ViewBankStatement extends ViewRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (BankStatement $record): bool => $record->status === BankStatementStatus::Draft),
            Action::make('import')->label('Import CSV')->authorize('import')
                ->visible(fn (BankStatement $record): bool => $record->status === BankStatementStatus::Draft)
                ->schema([
                    FileUpload::make('statement_file')
                        ->disk('local')
                        ->directory(fn (): string => 'bank-statements/'.Filament::getTenant()->getKey().'/incoming')
                        ->visibility('private')
                        ->storeFileNamesIn('original_file_name')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->rules(['extensions:csv'])
                        ->maxSize(10240)
                        ->required(),
                ])->action(function (BankStatement $record, array $data): void {
                    $path = Arr::get($data, 'statement_file');
                    $originalName = Arr::get($data, 'original_file_name');
                    app(ImportBankStatementAction::class)->handle(
                        $record,
                        is_string($path) ? $path : '',
                        is_string($originalName) ? $originalName : '',
                        Filament::auth()->user(),
                    );
                }),
        ];
    }
}
