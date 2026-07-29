<?php

namespace App\Filament\Resources\EmployeeFinancings\Schemas;

use App\Enums\EmployeeFinancingType;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeFinancingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')->relationship('company', 'name')->disabled()->dehydrated(false),
                Select::make('employment_id')
                    ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                        ->with('employee')->get()->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                        ])->all())
                    ->searchable()->required(),
                Select::make('type')->options(EmployeeFinancingType::class)->required(),
                DatePicker::make('request_date')->default(now())->required(),
                Textarea::make('purpose')->required()->columnSpanFull(),
                TextInput::make('principal_amount')->numeric()->minValue(0.0001)->required(),
                TextInput::make('finance_charge')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('installment_count')->integer()->minValue(1)->required(),
                DatePicker::make('first_due_date')->required(),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
