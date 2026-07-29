<?php

namespace App\Filament\Resources\EmploymentSeparations\Pages;

use App\Filament\Resources\EmploymentSeparations\EmploymentSeparationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmploymentSeparation extends EditRecord
{
    protected static string $resource = EmploymentSeparationResource::class;

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
