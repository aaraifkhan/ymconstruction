<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Actions\Banking\CloseBankReconciliationAction;
use App\Actions\Banking\MatchBankStatementLineAction;
use App\Actions\Banking\PostBankReconciliationAdjustmentAction;
use App\Actions\Banking\ReopenBankReconciliationAction;
use App\Actions\Banking\UnmatchBankStatementLineAction;
use App\Enums\BankReconciliationStatus;
use App\Enums\JournalStatus;
use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementLine;
use App\Models\JournalLine;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewBankReconciliation extends ViewRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (BankReconciliation $record): bool => $record->status === BankReconciliationStatus::Draft),
            Action::make('match')->authorize('match')->label('Match activity')
                ->visible(fn (BankReconciliation $record): bool => $record->isOpen())
                ->schema([
                    Select::make('statement_line_id')->label('Statement line')
                        ->options(fn (): array => $this->statementLineOptions())->searchable()->required(),
                    Select::make('journal_line_id')->label('Posted bank journal line')
                        ->options(fn (): array => $this->journalLineOptions())->searchable()->required(),
                    TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                ])->action(fn (BankReconciliation $record, array $data) => app(MatchBankStatementLineAction::class)->handle(
                    $record,
                    BankStatementLine::query()->findOrFail($data['statement_line_id']),
                    JournalLine::query()->findOrFail($data['journal_line_id']),
                    (string) $data['amount'],
                    Filament::auth()->user(),
                )),
            Action::make('unmatch')->authorize('unmatch')->label('Remove match')->color('warning')
                ->visible(fn (BankReconciliation $record): bool => $record->isOpen())
                ->schema([
                    Select::make('match_id')->options(fn (): array => BankReconciliationMatch::query()
                        ->where('bank_reconciliation_id', $this->getRecord()->getKey())
                        ->with(['bankStatementLine', 'journalLine.journalEntry'])->latest()->get()
                        ->mapWithKeys(fn (BankReconciliationMatch $match): array => [
                            $match->getKey() => "Statement #{$match->bankStatementLine->line_number} ↔ {$match->journalLine->journalEntry->voucher_number} — {$match->amount}",
                        ])->all())->searchable()->required(),
                ])->action(fn (array $data) => app(UnmatchBankStatementLineAction::class)->handle(
                    BankReconciliationMatch::query()->findOrFail($data['match_id']),
                    Filament::auth()->user(),
                )),
            Action::make('adjust')->authorize('adjust')->label('Post adjustment')->color('warning')
                ->visible(fn (BankReconciliation $record): bool => $record->isOpen())
                ->schema([
                    Select::make('statement_line_id')->label('Unmatched statement line')
                        ->options(fn (): array => $this->statementLineOptions())->searchable()->required(),
                    Select::make('adjustment_account_id')->label('Adjustment account')
                        ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                            ->where('is_active', true)->where('allows_manual_posting', true)
                            ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                                $account->getKey() => "{$account->code} — {$account->name}",
                            ])->all())->searchable()->required(),
                    Textarea::make('reason')->required(),
                ])->action(fn (BankReconciliation $record, array $data) => app(PostBankReconciliationAdjustmentAction::class)->handle(
                    $record,
                    BankStatementLine::query()->findOrFail($data['statement_line_id']),
                    Account::query()->findOrFail($data['adjustment_account_id']),
                    $data['reason'],
                    Filament::auth()->user(),
                )),
            Action::make('close')->authorize('close')->color('success')
                ->visible(fn (BankReconciliation $record): bool => $record->isOpen())
                ->requiresConfirmation()
                ->action(fn (BankReconciliation $record) => app(CloseBankReconciliationAction::class)
                    ->handle($record, Filament::auth()->user())),
            Action::make('reopen')->authorize('reopen')->color('danger')
                ->visible(fn (BankReconciliation $record): bool => $record->status === BankReconciliationStatus::Closed)
                ->schema([Textarea::make('reason')->required()])
                ->action(fn (BankReconciliation $record, array $data) => app(ReopenBankReconciliationAction::class)
                    ->handle($record, Filament::auth()->user(), $data['reason'])),
        ];
    }

    /** @return array<int, string> */
    private function statementLineOptions(): array
    {
        return BankStatementLine::query()
            ->where('bank_statement_id', $this->getRecord()->bank_statement_id)
            ->withSum('reconciliationMatches as matched_amount', 'amount')
            ->orderBy('line_number')->get()
            ->filter(fn (BankStatementLine $line): bool => bccomp(
                $line->statementAmount(),
                (string) ($line->matched_amount ?? 0),
                4,
            ) === 1)
            ->mapWithKeys(fn (BankStatementLine $line): array => [
                $line->getKey() => "{$line->line_number}. {$line->transaction_date->toDateString()} — {$line->description} — {$line->statementAmount()}",
            ])->all();
    }

    /** @return array<int, string> */
    private function journalLineOptions(): array
    {
        $record = $this->getRecord();

        return JournalLine::query()
            ->where('company_id', $record->company_id)
            ->where('company_bank_account_id', $record->company_bank_account_id)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '>=', $record->period_start)
                ->whereDate('transaction_date', '<=', $record->period_end))
            ->with(['journalEntry'])->withSum('bankReconciliationMatches as matched_amount', 'amount')
            ->get()->filter(function (JournalLine $line): bool {
                $amount = bccomp((string) $line->debit, '0', 4) === 1 ? (string) $line->debit : (string) $line->credit;

                return bccomp($amount, (string) ($line->matched_amount ?? 0), 4) === 1;
            })->mapWithKeys(fn (JournalLine $line): array => [
                $line->getKey() => "{$line->journalEntry->voucher_number} — {$line->description} — ".
                    (bccomp((string) $line->debit, '0', 4) === 1 ? $line->debit : $line->credit),
            ])->all();
    }
}
