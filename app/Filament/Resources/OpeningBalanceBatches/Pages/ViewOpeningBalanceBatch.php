<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Pages;

use App\Filament\Resources\OpeningBalanceBatches\Actions\OpeningBalanceWorkflowActions;
use App\Filament\Resources\OpeningBalanceBatches\OpeningBalanceBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOpeningBalanceBatch extends ViewRecord
{
    protected static string $resource = OpeningBalanceBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            OpeningBalanceWorkflowActions::validate(),
            OpeningBalanceWorkflowActions::post(),
        ];
    }
}
