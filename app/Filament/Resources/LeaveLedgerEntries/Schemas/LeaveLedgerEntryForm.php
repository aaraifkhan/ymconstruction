<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Schemas;

use App\Enums\LeaveLedgerEntryType;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LeaveLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Leave Ledger Entry Details')
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
                        Select::make('leave_type_id')
                            ->relationship('leaveType', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        Select::make('entry_type')
                            ->options(LeaveLedgerEntryType::class)
                            ->required(),
                        DatePicker::make('effective_on')
                            ->required(),
                        TextInput::make('units')
                            ->label('Units (Days / Hours)')
                            ->required()
                            ->numeric(),
                        TextInput::make('source_type'),
                        TextInput::make('source_id')
                            ->numeric(),
                        Select::make('recorded_by_id')
                            ->relationship('recordedBy', 'name'),
                        Textarea::make('reason')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
