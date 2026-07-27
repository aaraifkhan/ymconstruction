<?php

namespace App\Filament\Resources\PayrollRuns\RelationManagers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\ProjectSite;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Payroll Entries';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof PayrollRun
            && Gate::allows('viewAmounts', $ownerRecord)
            && auth()->user()?->can('ViewAny:PayrollEntry');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('payable_days')->numeric()->minValue(0)->required(),
            TextInput::make('absence_deduction')->numeric()->minValue(0)->required(),
            TextInput::make('loan_advance_deduction')->label('Loan & advance deduction')->numeric()->minValue(0)->required(),
            TextInput::make('other_deduction')->numeric()->minValue(0)->required(),
            TextInput::make('bank_amount')->numeric()->minValue(0)->required(),
            TextInput::make('cash_amount')->numeric()->minValue(0)->required(),
            Textarea::make('remarks')->maxLength(2000)->columnSpanFull(),
            Repeater::make('projectAllocations')->relationship()
                ->label('Project / Cost Center payroll allocation')
                ->helperText('Required for Project Staff. Total must equal Gross Salary less Absence Deduction.')
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                    ...$data,
                    'company_id' => Filament::getTenant()->getKey(),
                ])
                ->schema([
                    Select::make('project_id')->options(fn (): array => Project::query()
                        ->whereBelongsTo(Filament::getTenant())->orderBy('code')
                        ->get()->mapWithKeys(fn (Project $project): array => [
                            $project->getKey() => "{$project->code} — {$project->name}",
                        ])->all())->searchable()->required(),
                    Select::make('project_site_id')->options(fn (): array => ProjectSite::query()
                        ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                    Select::make('cost_center_id')->options(fn (): array => CostCenter::query()
                        ->whereBelongsTo(Filament::getTenant())->orderBy('code')
                        ->get()->mapWithKeys(fn (CostCenter $center): array => [
                            $center->getKey() => "{$center->code} — {$center->name}",
                        ])->all())->searchable(),
                    Select::make('expense_account_id')->label('Direct labour expense account')
                        ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                            ->where('account_type', AccountType::Expense)->where('is_active', true)
                            ->where('allows_manual_posting', true)->orderBy('code')
                            ->get()->mapWithKeys(fn (Account $account): array => [
                                $account->getKey() => "{$account->code} — {$account->name}",
                            ])->all())->searchable()->required(),
                    TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                ])->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee_name')
            ->columns([
                TextColumn::make('employee_name')->searchable(),
                TextColumn::make('designation'),
                TextColumn::make('employment_category')->badge(),
                TextColumn::make('payable_days')->label('Pay for'),
                TextColumn::make('payable_basic')->label('Basic')->formatStateUsing(fn ($state, PayrollEntry $record): string => $record->currency_code.' '.number_format((float) $state, 2)),
                TextColumn::make('gross_salary')->formatStateUsing(fn ($state, PayrollEntry $record): string => number_format((float) $state, 2)),
                TextColumn::make('net_salary')->formatStateUsing(fn ($state, PayrollEntry $record): string => number_format((float) $state, 2)),
                TextColumn::make('payment_mode')->state(fn (PayrollEntry $record): string => $record->paymentMode()),
            ])
            ->defaultGroup('employment_category')
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }
}
