<?php

namespace App\Filament\Resources\EmploymentCompensation\Schemas;

use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class EmploymentCompensationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Compensation period')
                ->schema([
                    TextEntry::make('employment.employee.full_name')->label('Employee'),
                    TextEntry::make('employment.employee_code')->label('Employee code'),
                    TextEntry::make('status')
                        ->formatStateUsing(fn (CompensationStatus $state): string => $state->getLabel())
                        ->badge()
                        ->color(fn (CompensationStatus $state): string => $state->getColor()),
                    TextEntry::make('effective_from')->date(),
                    TextEntry::make('effective_to')->date()->placeholder('Current / open-ended'),
                    TextEntry::make('currency_code')->label('Currency'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Protected salary amounts')
                ->schema([
                    TextEntry::make('basic_salary')
                        ->label('Basic salary')
                        ->state(fn (EmploymentCompensation $record): string => $record->formattedAmount('basic_salary')),
                    TextEntry::make('house_travel_allowance')
                        ->label('House & travel')
                        ->state(fn (EmploymentCompensation $record): string => $record->formattedAmount('house_travel_allowance')),
                    TextEntry::make('food_allowance')
                        ->label('Food')
                        ->state(fn (EmploymentCompensation $record): string => $record->formattedAmount('food_allowance')),
                    TextEntry::make('other_allowance')
                        ->label('Other')
                        ->state(fn (EmploymentCompensation $record): string => $record->formattedAmount('other_allowance')),
                    TextEntry::make('gross_salary')
                        ->label('Gross salary')
                        ->state(fn (EmploymentCompensation $record): string => $record->currency_code.' '.number_format($record->grossSalary(), 2))
                        ->weight('bold'),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ])
                ->visible(fn (EmploymentCompensation $record): bool => Gate::allows('viewAmounts', $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Workflow evidence')
                ->schema([
                    TextEntry::make('createdBy.name')->label('Created by')->placeholder('—'),
                    TextEntry::make('submittedBy.name')->label('Submitted by')->placeholder('—'),
                    TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                    TextEntry::make('approvedBy.name')->label('Approved by')->placeholder('—'),
                    TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                    TextEntry::make('rejectedBy.name')->label('Rejected by')->placeholder('—'),
                    TextEntry::make('rejected_at')->dateTime()->placeholder('—'),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
