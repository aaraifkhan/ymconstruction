<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Employee profile')
                ->schema([
                    TextEntry::make('full_name')->label('Full name'),
                    IconEntry::make('is_active')->label('Active profile')->boolean(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Identity information')
                ->schema([
                    TextEntry::make('father_or_husband_name')->label("Father's / husband's name")->placeholder('—'),
                    TextEntry::make('cnic'),
                    TextEntry::make('date_of_birth')->date()->placeholder('—'),
                    TextEntry::make('gender')->formatStateUsing(fn (?Gender $state): string => $state?->label() ?? '—'),
                    TextEntry::make('marital_status')->formatStateUsing(fn (?MaritalStatus $state): string => $state?->label() ?? '—'),
                    TextEntry::make('nationality')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewIdentity', $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Contact information')
                ->schema([
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('mobile')->placeholder('—'),
                    TextEntry::make('alternate_contact')->label('Alternate contact')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewContact', $record))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Medical information')
                ->schema([
                    TextEntry::make('blood_group')->label('Blood group')->placeholder('—'),
                ])
                ->visible(fn (Employee $record): bool => Gate::allows('viewMedical', $record))
                ->columnSpanFull(),
            Section::make('Document compliance')
                ->schema([
                    TextEntry::make('hr_document_compliance')
                        ->label('Required documents')
                        ->state(function (Employee $record): string {
                            $company = Filament::getTenant();

                            if ($company === null) {
                                return 'Company unavailable';
                            }

                            $missing = $record->missingRequiredHrDocumentTypes($company)->pluck('name');

                            return $missing->isEmpty()
                                ? 'Complete — no required document is missing'
                                : 'Missing: '.$missing->join(', ');
                        }),
                ])
                ->columnSpanFull(),
        ]);
    }
}
