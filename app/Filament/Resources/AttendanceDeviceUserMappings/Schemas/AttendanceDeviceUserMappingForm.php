<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Schemas;

use App\Filament\Support\CompanyContextField;
use App\Models\AttendanceDevice;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceDeviceUserMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Biometric Device User Mapping')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('attendance_device_id')
                            ->label('Biometric Device')
                            ->options(fn (): array => AttendanceDevice::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('employment_id')
                            ->label('Employee')
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
                            ->label('Biometric / Machine User ID')
                            ->required()
                            ->maxLength(191),
                        DatePicker::make('effective_from')
                            ->label('Effective From')
                            ->default(now()->toDateString())
                            ->required(),
                        DatePicker::make('effective_to')
                            ->label('Effective To'),
                        Textarea::make('notes')
                            ->label('Mapping Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
