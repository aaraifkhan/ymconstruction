<?php

namespace App\Filament\Resources\DepartmentTeams\Pages;

use App\Filament\Resources\DepartmentTeams\DepartmentTeamResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDepartmentTeam extends ViewRecord
{
    protected static string $resource = DepartmentTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
