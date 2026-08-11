<?php

namespace App\Filament\Resources\AttendancePunches\Schemas;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendancePunchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CompanyContextField::make(),
                Select::make('employment_id')
                    ->label('Employment')
                    ->options(fn (): array => Employment::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->with('employee')
                        ->get()
                        ->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee_code} — {$employment->employee?->full_name}",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                DateTimePicker::make('punched_at')
                    ->required(),
                Select::make('direction')
                    ->options(AttendancePunchDirection::class)
                    ->required(),
                Select::make('status')
                    ->options(AttendancePunchStatus::class)
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('created_by_id')
                    ->relationship('createdBy', 'name')
                    ->disabled(),
                Select::make('approved_by_id')
                    ->relationship('approvedBy', 'name')
                    ->disabled(),
                DateTimePicker::make('approved_at')->disabled(),
                Textarea::make('rejection_reason')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
