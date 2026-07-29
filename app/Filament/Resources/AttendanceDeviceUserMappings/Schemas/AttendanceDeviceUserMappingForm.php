<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Schemas;

use App\Models\AttendanceDevice;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceDeviceUserMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('attendance_device_id')
                    ->options(fn (): array => AttendanceDevice::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                Select::make('employment_id')
                    ->options(fn (): array => Employment::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->with('employee')
                        ->get()
                        ->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                        ])->all())
                    ->searchable()
                    ->required(),
                TextInput::make('external_user_id')
                    ->required()
                    ->maxLength(191),
                DatePicker::make('effective_from')
                    ->default(now()->toDateString())
                    ->required(),
                DatePicker::make('effective_to'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
