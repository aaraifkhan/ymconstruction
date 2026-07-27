<?php

namespace App\Filament\Resources\JoiningLetters\Schemas;

use App\Models\Employment;
use App\Models\JoiningLetter;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Unique;

class JoiningLetterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Joining letter setup')
                ->description('Save changes, then use “Regenerate from Template” to refresh the protected letter snapshot.')
                ->schema([
                    Select::make('employment_id')
                        ->label('Employee employment')
                        ->options(fn (): array => Employment::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->with('employee')
                            ->get()
                            ->mapWithKeys(fn (Employment $employment): array => [
                                $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('joining_letter_template_id')
                        ->label('Template')
                        ->relationship(
                            name: 'template',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('letter_number')
                        ->label('Letter number')
                        ->required()
                        ->maxLength(100)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                'company_id',
                                Filament::getTenant()?->getKey(),
                            ),
                        ),
                    DatePicker::make('letter_date')->label('Letter date')->default(today())->required(),
                    DatePicker::make('employment_effective_date')
                        ->label('Employment effective date')
                        ->default(today())
                        ->required(),
                    TextInput::make('compensation_amount')
                        ->label('Compensation amount')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn (string $operation, ?JoiningLetter $record): bool => self::canManageCompensation($operation, $record)),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->length(3)
                        ->required()
                        ->visible(fn (string $operation, ?JoiningLetter $record): bool => self::canManageCompensation($operation, $record)),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function canManageCompensation(string $operation, ?JoiningLetter $record): bool
    {
        if ($operation === 'create') {
            return auth()->user()?->can('ManageCompensation:JoiningLetter') ?? false;
        }

        return $record !== null && Gate::allows('manageCompensation', $record);
    }
}
