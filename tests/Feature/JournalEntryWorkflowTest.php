<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Reports\TrialBalanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_balanced_journal_uses_maker_checker_and_posts_idempotently(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $entry = $this->draftEntry($company, $maker);
        $this->addBalancedLines($entry);

        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        $firstRequest = $entry->fresh();
        $staleDuplicateRequest = $entry->fresh();
        $posted = app(PostJournalEntryAction::class)->handle($firstRequest, $poster);
        $again = app(PostJournalEntryAction::class)->handle($staleDuplicateRequest, $poster);

        $this->assertSame(JournalStatus::Posted, $posted->status);
        $this->assertSame('JV-2026-000001', $posted->voucher_number);
        $this->assertSame($posted->getKey(), $again->getKey());
        $this->assertSame('100.0000', $posted->debit_total);
        $this->assertSame(2, $posted->lines()->count());
        $this->assertSame(2, $company->voucherSequences()->where('voucher_type', VoucherType::Journal)->value('next_number'));
    }

    public function test_preparer_cannot_approve_or_post_own_journal(): void
    {
        [$company, $maker] = $this->foundation();
        $entry = $this->draftEntry($company, $maker);
        $this->addBalancedLines($entry);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);

        $this->expectException(ValidationException::class);
        app(ApproveJournalEntryAction::class)->handle($entry, $maker);
    }

    public function test_minimum_four_decimal_amount_posts_without_rounding_drift(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $entry = $this->draftEntry($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => '0.0001']);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $income->getKey(), 'credit' => '0.0001']);

        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        $posted = app(PostJournalEntryAction::class)->handle($entry, $poster);

        $this->assertSame('0.0001', $posted->debit_total);
        $this->assertSame($posted->debit_total, $posted->credit_total);
    }

    public function test_posted_journal_is_immutable_and_reversal_is_linked_and_idempotent(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $entry = $this->draftEntry($company, $maker);
        $this->addBalancedLines($entry);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        app(PostJournalEntryAction::class)->handle($entry, $poster);

        $reversal = app(ReverseJournalEntryAction::class)->handle($entry, $poster, CarbonImmutable::parse('2026-07-20'), 'Incorrect classification');
        $again = app(ReverseJournalEntryAction::class)->handle($entry, $poster, CarbonImmutable::parse('2026-07-20'), 'Duplicate request');

        $this->assertSame($reversal->getKey(), $again->getKey());
        $this->assertSame(VoucherType::Reversal, $reversal->voucher_type);
        $this->assertSame($entry->getKey(), $reversal->reverses_entry_id);
        $this->assertSame(JournalStatus::Reversed, $entry->fresh()->status);
        $cashRow = app(TrialBalanceReport::class)->forCompany($company, CarbonImmutable::parse('2026-07-31'))
            ->firstWhere('code', '1111');
        $this->assertSame('0.0000', $cashRow['natural_balance']);

        $this->expectException(ValidationException::class);
        $entry->fresh()->update(['description' => 'Silent edit']);
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

    private function draftEntry(Company $company, User $maker): JournalEntry
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        return JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'description' => 'Synthetic journal', 'prepared_by_id' => $maker->getKey(),
        ]);
    }

    private function addBalancedLines(JournalEntry $entry): void
    {
        $cash = $entry->company->accounts()->where('code', '1111')->firstOrFail();
        $income = $entry->company->accounts()->where('code', '4700')->firstOrFail();
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $entry->company_id, 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 100]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $entry->company_id, 'line_number' => 2, 'account_id' => $income->getKey(), 'credit' => 100]);
    }
}
