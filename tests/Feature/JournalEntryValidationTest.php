<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\CloseFinancialPeriodAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unbalanced_journal_cannot_be_submitted(): void
    {
        [$company, $maker] = $this->foundation();
        $entry = $this->entry($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 100]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $income->getKey(), 'credit' => 99]);

        $this->expectException(ValidationException::class);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
    }

    public function test_cross_company_account_is_rejected(): void
    {
        [$company, $maker] = $this->foundation();
        [$otherCompany] = $this->foundation();
        $entry = $this->entry($company, $maker);
        $otherAccount = $otherCompany->accounts()->where('code', '1111')->firstOrFail();

        $this->expectException(ValidationException::class);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $otherAccount->getKey(), 'debit' => 100]);
    }

    public function test_closed_period_blocks_an_approved_backdated_posting(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $entry = $this->entry($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 100]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $income->getKey(), 'credit' => 100]);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        app(CloseFinancialPeriodAction::class)->handle($entry->financialPeriod, $approver);

        $this->expectException(ValidationException::class);
        app(PostJournalEntryAction::class)->handle($entry, $poster);
    }

    public function test_cross_company_accounting_source_is_rejected(): void
    {
        [$company, $maker] = $this->foundation();
        [$otherCompany] = $this->foundation();
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        $this->expectException(ValidationException::class);
        JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'description' => 'Invalid source', 'prepared_by_id' => $maker->getKey(),
            'source_type' => Company::class, 'source_id' => $otherCompany->getKey(),
        ]);
    }

    public function test_company_idempotency_key_rejects_duplicate_journal_requests(): void
    {
        [$company, $maker] = $this->foundation();
        $first = $this->entry($company, $maker);
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        $this->expectException(QueryException::class);
        JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => $first->idempotency_key, 'transaction_date' => '2026-07-15',
            'description' => 'Duplicate request', 'prepared_by_id' => $maker->getKey(),
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

    private function entry(Company $company, User $maker): JournalEntry
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        return JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'description' => 'Validation journal', 'prepared_by_id' => $maker->getKey(),
        ]);
    }
}
