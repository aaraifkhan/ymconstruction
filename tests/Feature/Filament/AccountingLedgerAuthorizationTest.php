<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\VoucherType;
use App\Filament\Pages\AccountingReports;
use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingLedgerAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_journal_resource_and_workflow_policy_are_tenant_scoped(): void
    {
        [$currentCompany, $currentEntry] = $this->companyAndEntry();
        [$otherCompany, $otherEntry] = $this->companyAndEntry();
        $user = User::factory()->create();
        $user->companies()->attach($currentCompany, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo([
            Permission::findOrCreate('ViewAny:JournalEntry'),
            Permission::findOrCreate('View:JournalEntry'),
            Permission::findOrCreate('Create:JournalEntry'),
            Permission::findOrCreate('Submit:JournalEntry'),
            Permission::findOrCreate('View:AccountingReports'),
        ]);

        $this->actingAs($user);
        Filament::setTenant($currentCompany);
        Filament::bootCurrentPanel();

        Livewire::test(ListJournalEntries::class)
            ->assertCanSeeTableRecords([$currentEntry])
            ->assertCanNotSeeTableRecords([$otherEntry]);
        $period = $currentCompany->financialPeriods()->where('period_number', 1)->firstOrFail();
        $cash = $currentCompany->accounts()->where('code', '1111')->firstOrFail();
        $income = $currentCompany->accounts()->where('code', '4700')->firstOrFail();
        Livewire::test(CreateJournalEntry::class)
            ->assertFormFieldExists('voucher_type')
            ->assertFormFieldExists('financial_period_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'voucher_type' => VoucherType::Journal->value,
                'financial_period_id' => $period->getKey(),
                'transaction_date' => '2026-07-20',
                'description' => 'Created from Filament',
                'currency_code' => 'PKR',
                'lines' => [
                    ['account_id' => $cash->getKey(), 'debit' => 10, 'credit' => 0],
                    ['account_id' => $income->getKey(), 'debit' => 0, 'credit' => 10],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        Livewire::test(AccountingReports::class)->assertOk();

        $this->assertSame(2, JournalEntry::query()->where('description', 'Created from Filament')->firstOrFail()->lines()->count());
        $this->assertTrue(Gate::allows('submit', $currentEntry));
        $this->assertFalse(Gate::allows('submit', $otherEntry));
        $this->assertFalse($user->canAccessTenant($otherCompany));
    }

    /** @return array{Company, JournalEntry} */
    private function companyAndEntry(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $entry = JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'description' => 'Tenant journal', 'prepared_by_id' => User::factory()->create()->getKey(),
        ]);

        return [$company, $entry];
    }
}
