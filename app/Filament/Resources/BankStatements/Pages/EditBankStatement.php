<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Enums\BankStatementStatus;
use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\BankStatement;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (BankStatement $record): bool => $record->status === BankStatementStatus::Draft),
        ];
    }
}
