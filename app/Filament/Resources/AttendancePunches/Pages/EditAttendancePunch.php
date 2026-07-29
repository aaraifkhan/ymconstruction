<?php

namespace App\Filament\Resources\AttendancePunches\Pages;

use App\Filament\Resources\AttendancePunches\AttendancePunchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendancePunch extends EditRecord
{
    protected static string $resource = AttendancePunchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
