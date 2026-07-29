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
use Filament\Schemas\Schema;

class PayrollVariableComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employment_id')->options(fn (): array => Employment::query()
                    ->whereBelongsTo(Filament::getTenant())->with('employee')->orderBy('employee_code')->get()
                    ->mapWithKeys(fn (Employment $employment): array => [
                        $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                    ])->all())->searchable()->required(),
                Select::make('type')->options(PayrollVariableComponentType::class)->required(),
                DatePicker::make('earning_period_start')->required(),
                DatePicker::make('earning_period_end')->required(),
                TextInput::make('amount')->numeric()->minValue(0.01)->prefix('PKR')->required(),
                Select::make('project_id')->options(fn (): array => Project::query()
                    ->whereBelongsTo(Filament::getTenant())->orderBy('code')->get()
                    ->mapWithKeys(fn (Project $project): array => [
                        $project->getKey() => "{$project->code} — {$project->name}",
                    ])->all())->searchable(),
                TextInput::make('source_reference')->required()->maxLength(255),
                Textarea::make('notes')->maxLength(2000)->columnSpanFull(),
            ])->columns(2);
    }
}
