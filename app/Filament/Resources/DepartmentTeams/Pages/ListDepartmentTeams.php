<?php

namespace App\Filament\Resources\DepartmentTeams\Pages;

use App\Filament\Resources\DepartmentTeams\DepartmentTeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartmentTeams extends ListRecords
{
    protected static string $resource = DepartmentTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
