<?php

namespace Tests\Feature;

use App\Actions\Accounting\ImportOpeningBalanceMigrationAction;
use App\Actions\Accounting\PrepareOpeningBalanceMigrationAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ReverseOpeningBalanceMigrationAction;
use App\Actions\Accounting\ValidateOpeningBalanceMigrationAction;
use App\Enums\AccountingProfile;
use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpeningBalanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_dry_run_import_reconciles_exactly_and_can_be_rolled_back(): void
    {
        [$company, $maker, $validator, $importer] = $this->foundation();
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $csv = "account_code,party_code,project_code,cost_center_code,description,debit,credit\n"
            ."1111,,,,Approved cash,250000.1250,0\n"
            ."3100,,,,Approved capital,0,250000.1250\n";

        $migration = app(PrepareOpeningBalanceMigrationAction::class)->handle(
            $period,
            $maker,
            CarbonImmutable::parse('2026-07-01'),
            'approved-trial-balance.csv',
            $csv,
        );
        $this->assertSame(2, $migration->valid_row_count);
        $this->assertSame('250000.1250', $migration->source_debit_total);
        app(ValidateOpeningBalanceMigrationAction::class)->handle($migration, $validator);
        $imported = app(ImportOpeningBalanceMigrationAction::class)->handle($migration, $importer);

        $this->assertSame(OpeningBalanceMigrationStatus::Imported, $imported->status);
        $this->assertSame('250000.1250', $imported->openingBalanceBatch->journalEntry->debit_total);
        $reversed = app(ReverseOpeningBalanceMigrationAction::class)->handle(
            $imported,
            $importer,
            CarbonImmutable::parse('2026-07-20'),
            'Approved migration rollback',
        );
        $this->assertSame(OpeningBalanceMigrationStatus::Reversed, $reversed->status);
        $this->assertNotNull($reversed->reversal_entry_id);
    }

    public function test_invalid_or_unbalanced_source_is_failed_without_posting_data(): void
    {
        [$company, $maker, $validator] = $this->foundation();
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $csv = "account_code,party_code,project_code,cost_center_code,description,debit,credit\n"
            ."DOES-NOT-EXIST,,,,Bad account,100,0\n"
            ."3100,,,,Unbalanced capital,0,90\n";
        $migration = app(PrepareOpeningBalanceMigrationAction::class)->handle(
            $period,
            $maker,
            CarbonImmutable::parse('2026-07-01'),
            'invalid.csv',
            $csv,
        );

        $failed = app(ValidateOpeningBalanceMigrationAction::class)->handle($migration, $validator);

        $this->assertSame(OpeningBalanceMigrationStatus::Failed, $failed->status);
        $this->assertNull($failed->opening_balance_batch_id);
        $this->assertSame(0, $company->journalEntries()->count());
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
}
