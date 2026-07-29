<?php

namespace App\Filament\Resources\PayrollRuns\RelationManagers;

use App\Models\PayrollEntryComponent;
use App\Models\PayrollRun;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Calculation Components & Source Evidence';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof PayrollRun
            && Gate::allows('viewAmounts', $ownerRecord)
            && auth()->user()?->can('ViewAny:PayrollEntryComponent');
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id')->columns([
            TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
            TextColumn::make('employment.employee.full_name')->label('Employee')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('nature')->badge(),
            TextColumn::make('quantity'),
            TextColumn::make('rate')->money('PKR'),
            TextColumn::make('amount')->money('PKR'),
            TextColumn::make('source_label')->label('Source')->state(fn (PayrollEntryComponent $record): string => class_basename($record->source_type).' #'.$record->source_id),
            TextColumn::make('source_checksum')->limit(12)->copyable(),
        ]);
    }
}
