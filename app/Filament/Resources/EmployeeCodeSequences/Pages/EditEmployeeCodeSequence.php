<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Pages;

use App\Filament\Resources\EmployeeCodeSequences\EmployeeCodeSequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeCodeSequence extends EditRecord
{
    protected static string $resource = EmployeeCodeSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
