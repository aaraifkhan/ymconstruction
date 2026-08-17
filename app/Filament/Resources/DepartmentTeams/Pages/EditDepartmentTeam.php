<?php

namespace App\Filament\Resources\DepartmentTeams\Pages;

use App\Filament\Resources\DepartmentTeams\DepartmentTeamResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartmentTeam extends EditRecord
{
    protected static string $resource = DepartmentTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
