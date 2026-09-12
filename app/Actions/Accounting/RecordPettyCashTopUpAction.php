<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RecordPettyCashTopUpAction
{
    public const SYSTEM_POSTER_EMAIL = 'system.journal.poster@ymconstruction.local';

    public function __construct(
        private SubmitJournalEntryAction $submitJournal,
        private ApproveJournalEntryAction $approveJournal,
        private PostJournalEntryAction $postJournal,
    ) {}

    public function handle(
        Company $company,
        User $actor,
        CarbonInterface $date,
        string $amount,
        string $sourceType, // 'director', 'bank', 'head_office_cash'
        string $description,
        ?int $companyBankAccountId = null,
        ?string $reference = null,
        bool $postImmediately = false,
    ): JournalEntry {
        if (bccomp($amount, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        if ($sourceType === 'bank' && $companyBankAccountId === null) {
            throw ValidationException::withMessages(['company_bank_account_id' => 'Select a source bank account.']);
        }

        return DB::transaction(function () use (
            $company,
            $actor,
            $date,
            $amount,
            $sourceType,
            $description,
            $companyBankAccountId,
            $reference,
            $postImmediately,
        ): JournalEntry {
            $period = FinancialPeriod::query()
                ->where('company_id', $company->getKey())
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->lockForUpdate()
                ->first();

            if ($period === null) {
                throw ValidationException::withMessages(['date' => 'An open financial period is required for the transaction date.']);
            }

            $pettyCashAccount = $this->resolveSystemMappedAccount(
                company: $company,
                key: AccountingMappingKey::SitePettyCash,
                accountCode: '1112',
                label: 'Site Petty Cash (1112)',
            );

            $creditAccount = match ($sourceType) {
                'director' => $this->resolveSystemMappedAccount(
                    company: $company,
                    key: AccountingMappingKey::DirectorLoan,
                    accountCode: '2220',
                    label: 'Director Loan (2220)',
                ),
                'head_office_cash' => $this->resolveSystemMappedAccount(
                    company: $company,
                    key: AccountingMappingKey::DefaultCash,
                    accountCode: '1111',
                    label: 'Head Office Cash (1111)',
                ),
                'bank' => $this->resolveBankMappedAccount($company, (int) $companyBankAccountId),
                default => throw ValidationException::withMessages(['source_type' => 'Invalid top-up source.']),
            };

            $journal = JournalEntry::create([
                'company_id' => $company->getKey(),
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Receipt,
                'idempotency_key' => (string) Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $date,
                'reference' => $reference,
                'description' => "Petty Cash Top-up ({$sourceType}): {$description}",
                'narration' => $description,
                'currency_code' => 'PKR',
                'prepared_by_id' => $actor->getKey(),
            ]);

            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 1,
                'account_id' => $pettyCashAccount->getKey(),
                'account_code_snapshot' => $pettyCashAccount->code,
                'account_name_snapshot' => $pettyCashAccount->name,
                'debit' => $amount,
                'credit' => '0.0000',
                'description' => $description,
            ]);

            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 2,
                'account_id' => $creditAccount->getKey(),
                'account_code_snapshot' => $creditAccount->code,
                'account_name_snapshot' => $creditAccount->name,
                'company_bank_account_id' => $companyBankAccountId,
                'debit' => '0.0000',
                'credit' => $amount,
                'description' => match ($sourceType) {
                    'director' => 'Director funded petty cash top-up',
                    'head_office_cash' => 'Cash transfer to petty cash float',
                    'bank' => 'Bank withdrawal / transfer to petty cash float',
                    default => $description,
                },
            ]);

            $this->submitJournal->handle($journal, $actor);

            if ($postImmediately) {
                $poster = $this->resolveIndependentPoster($actor);
                $this->approveJournal->handle($journal->fresh(), $poster);
                $journal = $this->postJournal->handle($journal->fresh(), $poster);
            }

            activity('petty_cash')->causedBy($actor)->performedOn($journal)->event('top_up')
                ->withProperties([
                    'company_id' => $company->getKey(),
                    'source_type' => $sourceType,
                    'amount' => $amount,
                    'journal_entry_id' => $journal->getKey(),
                    'posted_immediately' => $postImmediately,
                    'status' => $journal->status->value,
                ])
                ->log('recorded petty cash top-up');

            return $journal->refresh();
        });
    }

    private function resolveSystemMappedAccount(
        Company $company,
        AccountingMappingKey $key,
        string $accountCode,
        string $label,
    ): Account {
        $mapping = AccountingMapping::query()
            ->where('company_id', $company->getKey())
            ->where('system_key', $key)
            ->with('account')
            ->first();

        if ($mapping?->account && $mapping->is_active && $mapping->account->is_active) {
            return $mapping->account;
        }

        $account = Account::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->where('allows_manual_posting', true)
            ->where(function ($query) use ($key, $accountCode): void {
                $query->where('system_key', $key->value)
                    ->orWhere('code', $accountCode);
            })
            ->orderByRaw('CASE WHEN system_key = ? THEN 0 WHEN code = ? THEN 1 ELSE 2 END', [$key->value, $accountCode])
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'source_type' => "{$label} account/mapping is missing for {$company->name}. Run AccountingFoundationSeeder or create account {$accountCode} with an active mapping.",
            ]);
        }

        AccountingMapping::updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'system_key' => $key,
            ],
            [
                'account_id' => $account->getKey(),
                'company_bank_account_id' => null,
                'is_active' => true,
            ],
        );

        return $account;
    }

    private function resolveBankMappedAccount(Company $company, int $companyBankAccountId): Account
    {
        $mapping = AccountingMapping::query()
            ->where('company_id', $company->getKey())
            ->where('company_bank_account_id', $companyBankAccountId)
            ->where('is_active', true)
            ->with('account')
            ->first();

        if ($mapping?->account) {
            return $mapping->account;
        }

        throw ValidationException::withMessages([
            'company_bank_account_id' => "Source bank account mapping is missing or has no linked account for {$company->name}.",
        ]);
    }

    /**
     * Maker-checker requires a different actor from the preparer for approve/post.
     * Prefer another super_admin; otherwise use a dedicated system poster account.
     */
    private function resolveIndependentPoster(User $preparer): User
    {
        $existingPoster = User::query()
            ->whereKeyNot($preparer->getKey())
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->orderBy('id')
            ->first();

        if ($existingPoster !== null) {
            return $existingPoster;
        }

        $poster = User::query()->firstOrCreate(
            ['email' => self::SYSTEM_POSTER_EMAIL],
            [
                'name' => 'System Journal Poster',
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ],
        );

        $role = Role::findOrCreate('super_admin');
        if (! $poster->hasRole($role)) {
            $poster->assignRole($role);
        }

        if ((int) $poster->getKey() === (int) $preparer->getKey()) {
            throw ValidationException::withMessages([
                'amount' => 'Top-up cannot be posted immediately because an independent approver/poster is unavailable.',
            ]);
        }

        return $poster;
    }
}
