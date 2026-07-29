<?php

namespace App\Filament\Resources\EmployeeClearances\Pages;

use App\Filament\Resources\EmployeeClearances\EmployeeClearanceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeClearance extends EditRecord
{
    protected static string $resource = EmployeeClearanceResource::class;

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
