<?php

namespace App\Filament\Resources\AppraisalCycles\Pages;

use App\Filament\Resources\AppraisalCycles\AppraisalCycleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAppraisalCycle extends ViewRecord
{
    protected static string $resource = AppraisalCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
