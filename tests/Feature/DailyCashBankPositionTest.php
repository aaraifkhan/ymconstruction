<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\VoucherType;
use App\Filament\Pages\DailyCashBankPositionPage;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Reports\DailyCashBankPositionReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyCashBankPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_daily_cash_bank_position_report_computes_exact_balances_and_items(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);
        $action = app(ProvisionCompanyAccountingFoundationAction::class);
        $action->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $approver = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $approver->assignRole($role);
        $poster->assignRole($role);

        $cashAccount = $company->accounts()->where('code', '1111')->firstOrFail();
        $expenseAccount = $company->accounts()->where('code', '5200')->firstOrFail();
        $revenueAccount = $company->accounts()->where('code', '4100')->firstOrFail();

        $bank = CompanyBankAccount::factory()->for($company)->create(['bank_name' => 'Faysal Bank (YM)']);
        $bankMapping = AccountingMapping::where('company_bank_account_id', $bank->getKey())->firstOrFail();
        $bankAccount = $bankMapping->account;

        // 1. Historical Transaction (Day before: 2026-08-09)
        // Opening receipt into Cash: PKR 50,000
        $this->postJournal(
            $company, $maker, $approver, $poster,
            $cashAccount->getKey(), $revenueAccount->getKey(),
            50000, '2026-08-09', 'Initial Cash Deposit'
        );

        // 2. Day of Report: 2026-08-10
        // A. Bank Receipt: PKR 100,000
        $this->postJournal(
            $company, $maker, $approver, $poster,
            $bankAccount->getKey(), $revenueAccount->getKey(),
            100000, '2026-08-10', 'Client Running Bill 01 Received'
        );

        // B. Cash Payment: PKR 15,000 for Fuel
        $this->postJournal(
            $company, $maker, $approver, $poster,
            $expenseAccount->getKey(), $cashAccount->getKey(),
            15000, '2026-08-10', 'Site generator fuel payment'
        );

        // Execute Report
        $reportService = app(DailyCashBankPositionReport::class);
        $result = $reportService->forCompany($company, CarbonImmutable::parse('2026-08-10'));

        $this->assertSame('2026-08-10', $result['date']);
        $this->assertSame('50000.0000', $result['totals']['opening_balance']);
        $this->assertSame('100000.0000', $result['totals']['receipts_total']);
        $this->assertSame('150000.0000', $result['totals']['grand_total']);
        $this->assertSame('15000.0000', $result['totals']['payments_total']);
        $this->assertSame('135000.0000', $result['totals']['closing_balance']);

        $this->assertCount(1, $result['receipt_items']);
        $this->assertSame('100000.0000', $result['receipt_items']->first()['amount']);

        $this->assertCount(1, $result['payment_items']);
        $this->assertSame('15000.0000', $result['payment_items']->first()['amount']);
    }

    public function test_daily_cash_bank_position_page_renders_and_supports_date_navigation(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(DailyCashBankPositionPage::class)
            ->assertSuccessful()
            ->set('reportDate', '2026-08-10')
            ->call('nextDay')
            ->assertSet('reportDate', '2026-08-11')
            ->call('previousDay')
            ->assertSet('reportDate', '2026-08-10')
            ->call('setToday')
            ->assertSet('reportDate', today()->toDateString());
    }

    private function postJournal(
        Company $company,
        User $maker,
        User $approver,
        User $poster,
        int $debitAccountId,
        int $creditAccountId,
        int $amount,
        string $date,
        string $narration
    ): void {
        $period = $company->financialPeriods()->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->firstOrFail();
        $entry = JournalEntry::create([
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(),
            'transaction_date' => $date,
            'description' => $narration,
            'narration' => $narration,
            'prepared_by_id' => $maker->getKey(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 1,
            'account_id' => $debitAccountId,
            'debit' => $amount,
            'narration' => $narration,
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $company->getKey(),
            'line_number' => 2,
            'account_id' => $creditAccountId,
            'credit' => $amount,
            'narration' => $narration,
        ]);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        app(PostJournalEntryAction::class)->handle($entry, $poster);
    }
}
