<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\FinancialPeriodStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ValidateJournalEntryAction
{
    public function __construct(
        private ?CheckAccountAvailableBalanceAction $balanceService = null,
    ) {
        $this->balanceService ??= app(CheckAccountAvailableBalanceAction::class);
    }

    /** @return array{debit_total:string, credit_total:string, line_count:int} */
    public function handle(JournalEntry $entry, bool $requireOpenPeriod = true): array
    {
        $entry->loadMissing(['financialPeriod', 'lines.account']);
        $period = $entry->financialPeriod;

        if ($entry->transaction_date->lt($period->starts_on) || $entry->transaction_date->gt($period->ends_on)) {
            throw ValidationException::withMessages(['transaction_date' => 'Transaction date must be inside the selected financial period.']);
        }

        if ($requireOpenPeriod && $period->status !== FinancialPeriodStatus::Open) {
            throw ValidationException::withMessages(['financial_period_id' => 'Only an open financial period accepts postings.']);
        }

        $settings = AccountingSetting::withoutGlobalScopes()->where('company_id', $entry->company_id)->firstOrFail();
        if ($entry->currency_code !== $settings->base_currency_code) {
            throw ValidationException::withMessages(['currency_code' => 'Journal currency must match the company base currency.']);
        }

        /** @var Collection<int, JournalLine> $lines */
        $lines = $entry->lines;
        if ($lines->count() < 2) {
            throw ValidationException::withMessages(['lines' => 'A journal requires at least two lines.']);
        }

        $debitTotal = $lines->reduce(fn (string $total, $line): string => bcadd($total, (string) $line->debit, 4), '0.0000');
        $creditTotal = $lines->reduce(fn (string $total, $line): string => bcadd($total, (string) $line->credit, 4), '0.0000');

        if (bccomp($debitTotal, '0.0000', 4) !== 1 || bccomp($debitTotal, $creditTotal, 4) !== 0) {
            throw ValidationException::withMessages(['lines' => 'Journal debit and credit totals must be equal and greater than zero.']);
        }

        $isSystemGenerated = $entry->source_type !== null || in_array($entry->voucher_type, [VoucherType::OpeningBalance, VoucherType::Reversal], true);

        foreach ($lines as $line) {
            $account = $line->account;
            if (! $account->is_active || $account->children()->exists()) {
                throw ValidationException::withMessages(['lines' => "Account {$account->code} is inactive or non-posting."]);
            }

            if (! $isSystemGenerated && ! $account->allows_manual_posting) {
                throw ValidationException::withMessages(['lines' => "Account {$account->code} does not allow manual posting."]);
            }

            $debit = (string) $line->debit;
            $credit = (string) $line->credit;
            if (bccomp($debit, '0.0000', 4) > 0 && bccomp($credit, '0.0000', 4) > 0) {
                throw ValidationException::withMessages(['lines' => "Line {$line->line_number} cannot have both Debit and Credit amounts."]);
            }
            if (bccomp($debit, '0.0000', 4) === 0 && bccomp($credit, '0.0000', 4) === 0) {
                throw ValidationException::withMessages(['lines' => "Line {$line->line_number} must have either a Debit or Credit amount."]);
            }

            if (! $isSystemGenerated && bccomp($credit, '0.0000', 4) > 0 && $this->balanceService->isStrictCashAccount($account)) {
                $availableBalance = $this->balanceService->getAccountBalance($entry->company_id, $account);
                if (bccomp($availableBalance, $credit, 4) === -1) {
                    $availFormatted = number_format((float) $availableBalance, 2);
                    $reqFormatted = number_format((float) $credit, 2);
                    throw ValidationException::withMessages([
                        'lines' => "Insufficient cash balance in account {$account->code} ({$account->name}). Available: PKR {$availFormatted}, Required: PKR {$reqFormatted}. Please record an opening balance or cash receipt first.",
                    ]);
                }
            }

            $this->validateRequiredDimensions($account, $line);
        }

        return ['debit_total' => $debitTotal, 'credit_total' => $creditTotal, 'line_count' => $lines->count()];
    }

    private function validateRequiredDimensions(Account $account, object $line): void
    {
        $partyKeys = [
            AccountingMappingKey::AccountsReceivable->value,
            AccountingMappingKey::AccountsPayable->value,
            AccountingMappingKey::VendorAdvances->value,
            AccountingMappingKey::RetentionReceivable->value,
            AccountingMappingKey::RetentionPayable->value,
            AccountingMappingKey::CustomerAdvances->value,
        ];

        if (in_array($account->system_key, $partyKeys, true) && $line->party_id === null) {
            throw ValidationException::withMessages(['lines' => "Account {$account->code} requires a party dimension."]);
        }

        $code = (int) $account->code;
        if ($code >= 7100 && $code <= 7299 && $line->project_id === null) {
            throw ValidationException::withMessages(['lines' => "Direct-cost account {$account->code} requires a project dimension."]);
        }
    }
}
