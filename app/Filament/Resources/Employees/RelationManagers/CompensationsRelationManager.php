<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Enums\CompensationStatus;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CompensationsRelationManager extends RelationManager
{
    protected static string $relationship = 'compensations';

    protected static ?string $title = 'Salary & Compensation';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Employee
            && Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', EmploymentCompensation::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Compensation period')
                ->schema([
                    Select::make('employment_id')
                        ->label('Employment')
                        ->options(function (RelationManager $livewire): array {
                            $employee = $livewire->getOwnerRecord();
                            $companyId = Filament::getTenant()?->getKey();

                            if (! $employee instanceof Employee || $companyId === null) {
                                return [];
                            }

                            return $employee->employments()
                                ->where('company_id', $companyId)
                                ->get()
                                ->mapWithKeys(fn (Employment $emp): array => [
                                    $emp->getKey() => "{$emp->employee_code} - {$emp->department?->name} / {$emp->designation?->name}",
                                ])
                                ->all();
                        })
                        ->default(function (RelationManager $livewire): ?int {
                            $employee = $livewire->getOwnerRecord();
                            $companyId = Filament::getTenant()?->getKey();

                            return $employee instanceof Employee && $companyId !== null
                                ? $employee->employments()->where('company_id', $companyId)->latest('joining_date')->value('id')
                                : null;
                        })
                        ->required(),
                    Hidden::make('company_id')
                        ->default(fn (): ?int => Filament::getTenant()?->getKey()),
                    DatePicker::make('effective_from')
                        ->required()
                        ->default(today()),
                    DatePicker::make('effective_to')
                        ->afterOrEqual('effective_from')
                        ->helperText('Leave blank while this compensation remains active.'),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->length(3)
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Monthly breakdown')
                ->description('Basic salary and monthly allowances.')
                ->schema([
                    TextInput::make('basic_salary')
                        ->label('Basic salary')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('house_travel_allowance')
                        ->label('House & travel allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('fuel_allowance')
                        ->label('Fuel allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('mobile_allowance')
                        ->label('Mobile allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('internet_allowance')
                        ->label('Internet allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('food_allowance')
                        ->label('Food allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('site_allowance')
                        ->label('Site allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('project_allowance')
                        ->label('Project allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('other_allowance')
                        ->label('Other allowance')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Textarea::make('notes')
                        ->label('Private notes')
                        ->maxLength(5000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $companyId = Filament::getTenant()?->getKey();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('employment_compensations.company_id', $companyId))
            ->defaultSort('effective_from', 'desc')
            ->columns([
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('effective_from')->date()->sortable(),
                TextColumn::make('effective_to')->date()->placeholder('Active / Current')->sortable(),
                TextColumn::make('basic_salary')
                    ->label('Basic salary')
                    ->formatStateUsing(fn (?string $state, EmploymentCompensation $record): string => $record->formattedAmount('basic_salary')),
                TextColumn::make('gross_salary')
                    ->label('Gross salary')
                    ->state(fn (EmploymentCompensation $record): string => $record->currency_code.' '.number_format($record->grossSalary(), 2)),
                TextColumn::make('approved_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CompensationStatus::class),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => Gate::allows('create', EmploymentCompensation::class))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = Filament::getTenant()?->getKey();
                        $data['created_by_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmploymentCompensation $record): bool => $record->status === CompensationStatus::Draft),
            ]);
    }
}
