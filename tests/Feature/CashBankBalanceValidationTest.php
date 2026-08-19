<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashBankBalanceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_voucher_from_zero_cash_balance_is_blocked(): void
    {
        [$company, $maker] = $this->foundation();

        $entry = $this->draftEntry($company, $maker, VoucherType::Payment);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $furniture = $company->accounts()->where('code', '1240')->firstOrFail();

        // Attempt to pay PKR 50,000 for furniture from cash when cash has 0 balance
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 1,
            'account_id' => $furniture->getKey(),
            'debit' => 50000,
            'credit' => 0,
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 2,
            'account_id' => $cash->getKey(),
            'debit' => 0,
            'credit' => 50000,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient cash balance in account 1111');

        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
    }

    public function test_payment_voucher_succeeds_after_cash_is_funded(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();

        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        $furniture = $company->accounts()->where('code', '1240')->firstOrFail();

        // 1. First fund cash with PKR 100,000 receipt
        $receipt = $this->draftEntry($company, $maker, VoucherType::Receipt);
        JournalLine::create([
            'journal_entry_id' => $receipt->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 1,
            'account_id' => $cash->getKey(),
            'debit' => 100000,
            'credit' => 0,
        ]);
        JournalLine::create([
            'journal_entry_id' => $receipt->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 2,
            'account_id' => $income->getKey(),
            'debit' => 0,
            'credit' => 100000,
        ]);

        app(SubmitJournalEntryAction::class)->handle($receipt, $maker);
        app(ApproveJournalEntryAction::class)->handle($receipt, $approver);
        app(PostJournalEntryAction::class)->handle($receipt, $poster);

        // Verify balance service reports PKR 100,000
        $balanceService = app(CheckAccountAvailableBalanceAction::class);
        $this->assertSame('100000.0000', $balanceService->getAccountBalance($company, $cash));

        // 2. Now pay PKR 50,000 for furniture from cash -> Must succeed
        $payment = $this->draftEntry($company, $maker, VoucherType::Payment);
        JournalLine::create([
            'journal_entry_id' => $payment->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 1,
            'account_id' => $furniture->getKey(),
            'debit' => 50000,
            'credit' => 0,
        ]);
        JournalLine::create([
            'journal_entry_id' => $payment->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 2,
            'account_id' => $cash->getKey(),
            'debit' => 0,
            'credit' => 50000,
        ]);

        $submitted = app(SubmitJournalEntryAction::class)->handle($payment, $maker);
        $this->assertSame(JournalStatus::Submitted, $submitted->status);

        app(ApproveJournalEntryAction::class)->handle($submitted, $approver);
        $postedPayment = app(PostJournalEntryAction::class)->handle($submitted, $poster);

        $this->assertSame(JournalStatus::Posted, $postedPayment->status);
        $this->assertSame('50000.0000', $balanceService->getAccountBalance($company, $cash));
    }

    public function test_line_with_both_debit_and_credit_is_rejected(): void
    {
        [$company, $maker] = $this->foundation();

        $entry = $this->draftEntry($company, $maker, VoucherType::Journal);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Each line requires a positive debit or positive credit, never both.');

        JournalLine::create([
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 1,
            'account_id' => $cash->getKey(),
            'debit' => 100,
            'credit' => 50,
        ]);
    }

    /** @return array{Company, User, User, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, ...$users->all()];
    }

    private function draftEntry(Company $company, User $maker, VoucherType $type = VoucherType::Journal): JournalEntry
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        return JournalEntry::create([
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => $type,
            'idempotency_key' => Str::uuid(),
            'transaction_date' => '2026-07-15',
            'description' => 'Test voucher',
            'prepared_by_id' => $maker->getKey(),
        ]);
    }
}
