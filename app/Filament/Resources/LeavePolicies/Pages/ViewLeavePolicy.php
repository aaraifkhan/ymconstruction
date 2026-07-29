<?php

namespace App\Filament\Resources\LeavePolicies\Pages;

use App\Filament\Resources\LeavePolicies\LeavePolicyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLeavePolicy extends ViewRecord
{
    protected static string $resource = LeavePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
