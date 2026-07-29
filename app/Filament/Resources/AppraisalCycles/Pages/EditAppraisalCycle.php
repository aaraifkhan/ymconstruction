<?php

namespace App\Filament\Resources\AppraisalCycles\Pages;

use App\Filament\Resources\AppraisalCycles\AppraisalCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppraisalCycle extends EditRecord
{
    protected static string $resource = AppraisalCycleResource::class;

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
