<?php

namespace App\Filament\Resources\EmploymentCompensation\Pages;

use App\Filament\Resources\EmploymentCompensation\Actions\EmploymentCompensationWorkflowActions;
use App\Filament\Resources\EmploymentCompensation\EmploymentCompensationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmploymentCompensation extends EditRecord
{
    protected static string $resource = EmploymentCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            EmploymentCompensationWorkflowActions::submit(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
