<?php

namespace App\Filament\Resources\JoiningLetters\Schemas;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class JoiningLetterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Letter summary')
                ->schema([
                    TextEntry::make('letter_number')->label('Letter number')->badge(),
                    TextEntry::make('status')
                        ->formatStateUsing(fn (JoiningLetterStatus $state): string => $state->label())
                        ->badge()
                        ->color(fn (JoiningLetterStatus $state): string => $state->color()),
                    TextEntry::make('employment.employee.full_name')->label('Employee'),
                    TextEntry::make('employment.employee_code')->label('Employee code'),
                    TextEntry::make('template.name')->label('Template')->placeholder('Deleted template'),
                    TextEntry::make('letter_date')->date(),
                    TextEntry::make('employment_effective_date')->label('Effective date')->date(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Protected letter snapshot')
                ->schema([
                    TextEntry::make('subject')->columnSpanFull(),
                    TextEntry::make('body')
                        ->state(fn (JoiningLetter $record): string => $record->bodyForDisplay(
                            Gate::allows('viewCompensation', $record),
                        ))
                        ->prose()
                        ->columnSpanFull(),
                    TextEntry::make('compensation_amount')
                        ->label('Compensation')
                        ->state(fn (JoiningLetter $record): string => $record->formattedCompensation())
                        ->visible(fn (JoiningLetter $record): bool => Gate::allows('viewCompensation', $record)),
                    TextEntry::make('content_checksum')->label('Issued content checksum')->placeholder('Not issued')->copyable(),
                ])
                ->visible(fn (JoiningLetter $record): bool => Gate::allows('viewSensitive', $record))
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
                    TextEntry::make('issuedBy.name')->label('Issued by')->placeholder('—'),
                    TextEntry::make('issued_at')->dateTime()->placeholder('—'),
                    TextEntry::make('accepted_by_name')->label('Accepted by')->placeholder('—'),
                    TextEntry::make('accepted_at')->dateTime()->placeholder('—'),
                    TextEntry::make('acceptance_notes')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
