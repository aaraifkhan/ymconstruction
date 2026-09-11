<?php

namespace App\Filament\Resources\EmployeeFinancings\Schemas;

use App\Enums\EmployeeFinancingType;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeFinancingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                CompanyContextField::make(),
                Section::make('Financing Request Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employment_id')
                            ->label('Employee')
                            ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                                ->with('employee')->get()->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                                ])->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Financing Type')
                            ->options(EmployeeFinancingType::class)
                            ->required()
                            ->live(),
                        Select::make('sub_category')
                            ->label('Sub-category')
                            ->options(function (callable $get): array {
                                $type = $get('type');
                                $typeEnum = $type instanceof EmployeeFinancingType
                                    ? $type
                                    : (is_string($type) ? EmployeeFinancingType::tryFrom($type) : null);

                                return match ($typeEnum) {
                                    EmployeeFinancingType::Loan => [
                                        'vehicle_loan' => 'Vehicle Loan',
                                        'personal_loan' => 'Personal Loan',
                                        'home_loan' => 'Home Loan',
                                        'business_loan' => 'Business Loan',
                                    ],
                                    EmployeeFinancingType::Advance => [
                                        'salary_advance' => 'Salary Advance',
                                        'medical_advance' => 'Medical Advance',
                                        'education_advance' => 'Education Advance',
                                        'travel_advance' => 'Travel Advance',
                                    ],
                                    default => [],
                                };
                            })
                            ->helperText('Specific category within the selected type.')
                            ->searchable(),
                        DatePicker::make('request_date')
                            ->label('Application Date')
                            ->default(now())
                            ->required(),
                        DatePicker::make('first_due_date')
                            ->label('First Recovery Due Date')
                            ->required(),
                        TextInput::make('principal_amount')
                            ->label('Principal Amount (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->minValue(0.0001)
                            ->required(),
                        TextInput::make('finance_charge')
                            ->label('Markup / Finance Charge (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        TextInput::make('installment_count')
                            ->label('Number of Monthly Installments')
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        Textarea::make('purpose')
                            ->label('Loan Purpose / Reason')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Internal Approval Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
