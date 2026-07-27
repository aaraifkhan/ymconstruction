<?php

namespace App\Filament\Resources\IntercompanyTransactions\Pages;

use App\Filament\Resources\IntercompanyTransactions\IntercompanyTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIntercompanyTransaction extends EditRecord
{
    protected static string $resource = IntercompanyTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
