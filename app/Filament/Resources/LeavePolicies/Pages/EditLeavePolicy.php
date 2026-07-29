<?php

namespace App\Filament\Resources\LeavePolicies\Pages;

use App\Filament\Resources\LeavePolicies\LeavePolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLeavePolicy extends EditRecord
{
    protected static string $resource = LeavePolicyResource::class;

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
