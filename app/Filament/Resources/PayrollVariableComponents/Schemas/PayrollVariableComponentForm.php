<?php

namespace App\Filament\Resources\PayrollVariableComponents\Schemas;

use App\Enums\PayrollVariableComponentType;
use App\Models\Employment;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollVariableComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payroll Variable Component Entry')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employment_id')
                            ->label('Employee')
                            ->options(fn (): array => Employment::query()
                                ->whereBelongsTo(Filament::getTenant())->with('employee')->orderBy('employee_code')->get()
                                ->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                                ])->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Component Type')
                            ->options(PayrollVariableComponentType::class)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('PKR')
                            ->required(),
                        DatePicker::make('earning_period_start')
                            ->label('Earning Period Start')
                            ->required(),
                        DatePicker::make('earning_period_end')
                            ->label('Earning Period End')
                            ->required(),
                        Select::make('project_id')
                            ->label('Costing Project')
                            ->options(fn (): array => Project::query()
                                ->whereBelongsTo(Filament::getTenant())->orderBy('code')->get()
                                ->mapWithKeys(fn (Project $project): array => [
                                    $project->getKey() => "{$project->code} — {$project->name}",
                                ])->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('source_reference')
                            ->label('Source / Approval Reference')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Notes & Justification')
                            ->rows(2)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
