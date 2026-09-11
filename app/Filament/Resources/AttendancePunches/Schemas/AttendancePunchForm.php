<?php

namespace App\Filament\Resources\AttendancePunches\Schemas;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendancePunchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Manual Attendance Punch Request')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
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
                            ->label('Punch Timestamp')
                            ->required(),
                        Select::make('direction')
                            ->label('Punch Direction')
                            ->options(AttendancePunchDirection::class)
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(AttendancePunchStatus::class)
                            ->default('pending')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('created_by_id')
                            ->label('Created By')
                            ->relationship('createdBy', 'name')
                            ->disabled(),
                        Select::make('approved_by_id')
                            ->label('Approved By')
                            ->relationship('approvedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('approved_at')->label('Approved At')->disabled(),
                        Textarea::make('reason')
                            ->label('Reason / Remarks')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
