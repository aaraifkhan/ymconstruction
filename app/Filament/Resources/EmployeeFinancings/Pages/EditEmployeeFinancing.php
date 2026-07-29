<?php

namespace App\Filament\Resources\EmployeeFinancings\Pages;

use App\Filament\Resources\EmployeeFinancings\EmployeeFinancingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeFinancing extends EditRecord
{
    protected static string $resource = EmployeeFinancingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_repayable'] = bcadd((string) $data['principal_amount'], (string) ($data['finance_charge'] ?? 0), 4);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
