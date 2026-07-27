<?php

namespace App\Filament\Resources\PayrollRuns\Pages;

use App\Filament\Resources\PayrollRuns\Actions\PayrollWorkflowActions;
use App\Filament\Resources\PayrollRuns\PayrollRunResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollRun extends EditRecord
{
    protected static string $resource = PayrollRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            PayrollWorkflowActions::generate(),
            PayrollWorkflowActions::submit(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
