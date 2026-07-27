<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\IntercompanyDirection;
use App\Filament\Pages\ConsolidatedReports;
use App\Filament\Resources\IntercompanyTransactions\Pages\ListIntercompanyTransactions;
use App\Filament\Resources\OpeningBalanceMigrations\Pages\ListOpeningBalanceMigrations;
use App\Filament\Resources\YearEndClosings\Pages\ListYearEndClosings;
use App\Models\Company;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use App\Reports\ConsolidatedFinancialReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase12AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_counterparty_can_see_only_its_pair_and_group_reports_require_full_scope(): void
    {
        [$root, $child, $unrelated] = $this->companies();
        $maker = User::factory()->create();
        $pair = $this->transaction($root, $child, $maker);
        $otherPair = $this->transaction($root, $unrelated, $maker);
        $user = User::factory()->create();
        $user->companies()->attach($child, ['is_active' => true, 'can_access_descendants' => false]);
        $this->grant($user, ['ViewAny:IntercompanyTransaction', 'View:IntercompanyTransaction']);
        $this->actingAs($user);
        Filament::setTenant($child);
        Filament::bootCurrentPanel();

        Livewire::test(ListIntercompanyTransactions::class)
            ->assertCanSeeTableRecords([$pair])
            ->assertCanNotSeeTableRecords([$otherPair]);

        $groupUser = User::factory()->create();
        $groupUser->companies()->attach($root, ['is_active' => true, 'can_access_descendants' => false]);
        $this->grant($groupUser, ['View:ConsolidatedReports']);
        $this->expectException(ValidationException::class);
        app(ConsolidatedFinancialReport::class)->forGroup(
            $groupUser,
            $root,
            CarbonImmutable::parse('2026-07-31'),
        );
    }

    public function test_authorized_accounting_control_pages_render_in_company_tenant(): void
    {
        [$root] = $this->companies();
        $user = User::factory()->create();
        $user->companies()->attach($root, ['is_active' => true, 'can_access_descendants' => true]);
        $this->grant($user, [
            'ViewAny:IntercompanyTransaction', 'ViewAny:YearEndClosing',
            'ViewAny:OpeningBalanceMigration', 'View:ConsolidatedReports',
        ]);
        $this->actingAs($user);
        Filament::setTenant($root);
        Filament::bootCurrentPanel();

        Livewire::test(ListIntercompanyTransactions::class)->assertSuccessful();
        Livewire::test(ListYearEndClosings::class)->assertSuccessful();
        Livewire::test(ListOpeningBalanceMigrations::class)->assertSuccessful();
        Livewire::test(ConsolidatedReports::class)->assertSuccessful();
    }

    /** @return array{Company, Company, Company} */
    private function companies(): array
    {
        $root = Company::factory()->create(['name' => '7-Orbit']);
        $child = Company::factory()->create(['name' => '7-Orbit IT', 'parent_company_id' => $root->getKey()]);
        $unrelated = Company::factory()->create(['name' => 'YM Construction']);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        foreach ([$root, $child, $unrelated] as $company) {
            app(ProvisionCompanyAccountingFoundationAction::class)->handle(
                $company,
                AccountingProfile::Generic,
                CarbonImmutable::parse('2026-07-15'),
            );
        }

        return [$root, $child, $unrelated];
    }

    private function transaction(Company $origin, Company $counterparty, User $maker): IntercompanyTransaction
    {
        return IntercompanyTransaction::create([
            'company_id' => $origin->getKey(),
            'counterparty_company_id' => $counterparty->getKey(),
            'idempotency_key' => Str::uuid(),
            'transaction_date' => '2026-07-15',
            'direction' => IntercompanyDirection::OriginReceivable,
            'amount' => 100,
            'origin_offset_account_id' => $origin->accounts()->where('code', '1111')->value('id'),
            'counterparty_offset_account_id' => $counterparty->accounts()->where('code', '6900')->value('id'),
            'description' => 'Tenant pair',
            'prepared_by_id' => $maker->getKey(),
        ]);
    }

    /** @param array<int, string> $permissions */
    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }
}
