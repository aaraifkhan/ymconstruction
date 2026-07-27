<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveIntercompanyTransactionAction;
use App\Actions\Accounting\PostIntercompanyTransactionAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ReverseIntercompanyTransactionAction;
use App\Actions\Accounting\SubmitIntercompanyTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\IntercompanyDirection;
use App\Enums\IntercompanyStatus;
use App\Models\Company;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use App\Reports\IntercompanyReconciliationReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntercompanyAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_independently_approved_pair_posts_reconciles_and_reverses_atomically(): void
    {
        [$origin, $counterparty, $maker, $originApprover, $counterpartyApprover, $poster] = $this->foundation();
        $transaction = $this->transaction($origin, $counterparty, $maker);

        app(SubmitIntercompanyTransactionAction::class)->handle($transaction, $maker);
        app(ApproveIntercompanyTransactionAction::class)->handleOrigin($transaction, $originApprover);
        app(ApproveIntercompanyTransactionAction::class)->handleCounterparty($transaction, $counterpartyApprover);
        $posted = app(PostIntercompanyTransactionAction::class)->handle($transaction, $poster);

        $this->assertSame(IntercompanyStatus::Posted, $posted->status);
        $this->assertNotNull($posted->origin_journal_entry_id);
        $this->assertNotNull($posted->counterparty_journal_entry_id);
        $this->assertSame('IC-2026-000001', $posted->originJournalEntry->voucher_number);
        $this->assertSame('IC-2026-000001', $posted->counterpartyJournalEntry->voucher_number);
        $report = app(IntercompanyReconciliationReport::class)->forCompanies(
            collect([$origin, $counterparty]),
            CarbonImmutable::parse('2026-07-31'),
        );
        $this->assertTrue($report->first()['reconciles']);
        $this->assertSame('1250.0000', $report->first()['due_from']);

        $reversed = app(ReverseIntercompanyTransactionAction::class)->handle(
            $posted,
            $poster,
            CarbonImmutable::parse('2026-07-20'),
            'Agreement cancelled',
        );
        $this->assertSame(IntercompanyStatus::Reversed, $reversed->status);
        $this->assertSame('0.0000', app(IntercompanyReconciliationReport::class)->forCompanies(
            collect([$origin, $counterparty]),
            CarbonImmutable::parse('2026-07-31'),
        )->first()['due_from']);
    }

    public function test_preparer_and_same_person_cannot_satisfy_independent_approvals(): void
    {
        [$origin, $counterparty, $maker, $approver] = $this->foundation();
        $transaction = $this->transaction($origin, $counterparty, $maker);
        app(SubmitIntercompanyTransactionAction::class)->handle($transaction, $maker);

        try {
            app(ApproveIntercompanyTransactionAction::class)->handleOrigin($transaction, $maker);
            $this->fail('Preparer approval should fail.');
        } catch (ValidationException) {
            $this->assertNull($transaction->fresh()->origin_approved_by_id);
        }

        app(ApproveIntercompanyTransactionAction::class)->handleOrigin($transaction, $approver);
        $this->expectException(ValidationException::class);
        app(ApproveIntercompanyTransactionAction::class)->handleCounterparty($transaction, $approver);
    }

    /** @return array{Company, Company, User, User, User, User} */
    private function foundation(): array
    {
        $origin = Company::factory()->create(['name' => '7-Orbit']);
        $counterparty = Company::factory()->create(['name' => '7-Orbit IT', 'parent_company_id' => $origin->getKey()]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        foreach ([$origin, $counterparty] as $company) {
            app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        }
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(4)->create();
        $users->each->assignRole($role);

        return [$origin, $counterparty, ...$users->all()];
    }

    private function transaction(Company $origin, Company $counterparty, User $maker): IntercompanyTransaction
    {
        return IntercompanyTransaction::create([
            'company_id' => $origin->getKey(),
            'counterparty_company_id' => $counterparty->getKey(),
            'idempotency_key' => Str::uuid(),
            'transaction_date' => '2026-07-15',
            'direction' => IntercompanyDirection::OriginReceivable,
            'amount' => 1250,
            'origin_offset_account_id' => $origin->accounts()->where('code', '1111')->firstOrFail()->getKey(),
            'counterparty_offset_account_id' => $counterparty->accounts()->where('code', '6900')->firstOrFail()->getKey(),
            'description' => 'Shared service recharge',
            'prepared_by_id' => $maker->getKey(),
        ]);
    }
}
