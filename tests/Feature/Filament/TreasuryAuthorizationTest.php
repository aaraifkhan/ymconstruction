<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Documents\CreateDocumentAction;
use App\Enums\AccountingProfile;
use App\Enums\DocumentClassification;
use App\Enums\TreasuryPurpose;
use App\Filament\Pages\TreasuryReports;
use App\Filament\Resources\BankReconciliations\Pages\ListBankReconciliations;
use App\Filament\Resources\BankStatements\Pages\ListBankStatements;
use App\Filament\Resources\TreasuryTransactions\Pages\CreateTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\ListTreasuryTransactions;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\DocumentCategory;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TreasuryAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
    }

    public function test_treasury_bank_and_reconciliation_resources_are_tenant_scoped(): void
    {
        [$company, $transaction, $statement, $reconciliation] = $this->foundation();
        [, $otherTransaction, $otherStatement, $otherReconciliation] = $this->foundation();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:TreasuryTransaction', 'View:TreasuryTransaction', 'Create:TreasuryTransaction',
            'Submit:TreasuryTransaction', 'ViewAny:BankStatement', 'View:BankStatement',
            'ViewAny:BankReconciliation', 'View:BankReconciliation', 'View:TreasuryReports',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(ListTreasuryTransactions::class)
            ->assertCanSeeTableRecords([$transaction])
            ->assertCanNotSeeTableRecords([$otherTransaction]);
        Livewire::test(ListBankStatements::class)
            ->assertCanSeeTableRecords([$statement])
            ->assertCanNotSeeTableRecords([$otherStatement]);
        Livewire::test(ListBankReconciliations::class)
            ->assertCanSeeTableRecords([$reconciliation])
            ->assertCanNotSeeTableRecords([$otherReconciliation]);
        Livewire::test(TreasuryReports::class)->assertSuccessful();

        $this->assertTrue(Gate::allows('submit', $transaction));
        $this->assertFalse(Gate::allows('submit', $otherTransaction));
    }

    public function test_create_page_exposes_controlled_payment_transfer_and_allocation_fields(): void
    {
        [$company] = $this->foundation();
        $user = User::factory()->create();
        $this->grant($user, $company, ['ViewAny:TreasuryTransaction', 'Create:TreasuryTransaction']);
        $this->useTenant($user, $company);

        Livewire::test(CreateTreasuryTransaction::class)
            ->assertFormFieldExists('type')
            ->assertFormFieldExists('purpose')
            ->assertFormFieldExists('source_account_id')
            ->assertFormFieldExists('destination_account_id')
            ->assertFormFieldExists('allocations');
    }

    public function test_private_evidence_links_to_same_company_treasury_and_bank_records_only(): void
    {
        Storage::fake('local');
        [$company, $transaction, $statement, $reconciliation] = $this->foundation();
        [, $otherTransaction] = $this->foundation();
        $user = User::factory()->create();
        $this->grant($user, $company, ['Create:Document']);
        $this->useTenant($user, $company);
        $category = DocumentCategory::factory()->for($company)->create(['is_active' => true]);

        foreach ([
            'treasury_transaction' => $transaction,
            'bank_statement' => $statement,
            'bank_reconciliation' => $reconciliation,
        ] as $scope => $record) {
            $path = "documents/{$company->getKey()}/incoming/{$scope}.pdf";
            Storage::disk('local')->put($path, "%PDF-1.4\n{$scope}");
            $document = app(CreateDocumentAction::class)->handle(
                $company,
                [
                    'document_category_id' => $category->getKey(),
                    'title' => str($scope)->headline()->toString(),
                    'classification' => DocumentClassification::Restricted->value,
                    'document_scope' => $scope,
                    'related_record_id' => $record->getKey(),
                ],
                $path,
                "{$scope}.pdf",
                $user,
            );
            $this->assertTrue($document->documentable->is($record));
        }

        $path = "documents/{$company->getKey()}/incoming/cross-company.pdf";
        Storage::disk('local')->put($path, "%PDF-1.4\ncross-company");
        $this->expectException(ValidationException::class);
        app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company payment',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'treasury_transaction',
                'related_record_id' => $otherTransaction->getKey(),
            ],
            $path,
            'cross-company.pdf',
            $user,
        );
    }

    /** @return array{Company, TreasuryTransaction, BankStatement, BankReconciliation} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Generic,
            CarbonImmutable::parse('2026-07-15'),
        );
        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $transaction = TreasuryTransaction::factory()->paymentFrom($company, $cash)->create([
            'purpose' => TreasuryPurpose::Other,
            'destination_account_id' => $expense,
            'transaction_date' => '2026-07-15',
        ]);
        $statement = BankStatement::factory()->create([
            'company_id' => $company,
            'company_bank_account_id' => $bank,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        $reconciliation = BankReconciliation::factory()->create([
            'company_id' => $company,
            'company_bank_account_id' => $bank,
            'bank_statement_id' => $statement,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'prepared_by_id' => User::factory(),
        ]);

        return [$company, $transaction, $statement, $reconciliation];
    }

    /** @param array<int, string> $permissions */
    private function grant(User $user, Company $company, array $permissions): void
    {
        $user->companies()->syncWithoutDetaching([
            $company->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    private function useTenant(User $user, Company $company): void
    {
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
