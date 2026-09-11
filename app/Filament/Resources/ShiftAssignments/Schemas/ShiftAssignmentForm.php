<?php

namespace App\Filament\Resources\ShiftAssignments\Schemas;

use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ShiftAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Shift Assignment Details')
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
                        Select::make('work_calendar_id')
                            ->relationship('workCalendar', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        Select::make('work_shift_id')
                            ->relationship('workShift', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        DatePicker::make('effective_from')
                            ->required(),
                        DatePicker::make('effective_to'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
