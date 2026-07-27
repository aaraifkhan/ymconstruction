<?php

namespace App\Filament\Resources\EmploymentCompensation\Pages;

use App\Filament\Resources\EmploymentCompensation\Actions\EmploymentCompensationWorkflowActions;
use App\Filament\Resources\EmploymentCompensation\EmploymentCompensationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmploymentCompensation extends ViewRecord
{
    protected static string $resource = EmploymentCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            EmploymentCompensationWorkflowActions::submit(),
            EmploymentCompensationWorkflowActions::approve(),
            EmploymentCompensationWorkflowActions::reject(),
        ];
    }
}
