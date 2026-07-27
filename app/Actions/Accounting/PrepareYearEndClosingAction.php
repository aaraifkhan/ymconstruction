<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\AccountType;
use App\Enums\YearEndClosingStatus;
use App\Models\AccountingMapping;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\YearEndClosing;
use App\Reports\TrialBalanceReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrepareYearEndClosingAction
{
    public function __construct(private TrialBalanceReport $trialBalance) {}

    public function handle(FinancialYear $year, User $actor): YearEndClosing
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('Create:YearEndClosing')) || ! $actor->canAccessTenant($year->company)) {
            throw ValidationException::withMessages(['authorization' => 'You are not authorized to prepare this year-end closing.']);
        }

        return DB::transaction(function () use ($year, $actor): YearEndClosing {
            $year = FinancialYear::query()->whereKey($year)->lockForUpdate()->firstOrFail();
            if ($year->yearEndClosing()->whereNot('status', YearEndClosingStatus::Reversed)->exists()) {
                throw ValidationException::withMessages(['financial_year_id' => 'This financial year already has an active closing.']);
            }
            $snapshot = $this->snapshot($year);
            $retainedEarningsAccountId = AccountingMapping::query()
                ->where('company_id', $year->company_id)
                ->where('system_key', AccountingMappingKey::RetainedEarnings)
                ->where('is_active', true)->value('account_id');
            if ($retainedEarningsAccountId === null) {
                throw ValidationException::withMessages(['accounting_mapping' => 'A retained earnings mapping is required.']);
            }
            $closing = YearEndClosing::create([
                'company_id' => $year->company_id,
                'financial_year_id' => $year->getKey(),
                'idempotency_key' => Str::uuid(),
                'status' => YearEndClosingStatus::Draft,
                'profit_or_loss' => $snapshot['profit_or_loss'],
                'calculation_checksum' => $snapshot['checksum'],
                'calculation_snapshot' => $snapshot['lines'],
                'retained_earnings_account_id' => $retainedEarningsAccountId,
                'prepared_by_id' => $actor->getKey(),
            ]);
            activity('year_end_closings')->causedBy($actor)->performedOn($closing)->event('prepared')
                ->withProperties(['profit_or_loss' => $snapshot['profit_or_loss'], 'checksum' => $snapshot['checksum']])
                ->log('prepared reproducible year-end closing');

            return $closing->refresh();
        });
    }

    /** @return array{lines:array<int,array<string,mixed>>,profit_or_loss:string,checksum:string} */
    public function snapshot(FinancialYear $year): array
    {
        $rows = $this->trialBalance->forCompany($year->company, $year->ends_on, $year->starts_on)
            ->whereIn('account_type', [AccountType::Revenue, AccountType::Expense])
            ->filter(fn (array $row): bool => bccomp((string) $row['natural_balance'], '0.0000', 4) !== 0)
            ->map(fn (array $row): array => [
                'account_id' => $row['account_id'],
                'code' => $row['code'],
                'account_type' => $row['account_type']->value,
                'natural_balance' => (string) $row['natural_balance'],
            ])->sortBy('code')->values()->all();
        $profit = array_reduce($rows, fn (string $total, array $row): string => $row['account_type'] === AccountType::Revenue->value
            ? bcadd($total, $row['natural_balance'], 4)
            : bcsub($total, $row['natural_balance'], 4), '0.0000');
        $payload = json_encode(['financial_year_id' => $year->getKey(), 'lines' => $rows, 'profit_or_loss' => $profit], JSON_THROW_ON_ERROR);

        return ['lines' => $rows, 'profit_or_loss' => $profit, 'checksum' => hash('sha256', $payload)];
    }
}
