<?php

namespace App\Filament\Resources\IntercompanyTransactions\Pages;

use App\Filament\Resources\IntercompanyTransactions\IntercompanyTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIntercompanyTransaction extends ViewRecord
{
    protected static string $resource = IntercompanyTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
