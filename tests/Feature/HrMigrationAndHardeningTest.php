<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Actions\Documents\ProvisionDefaultHrDocumentTypesAction;
use App\Actions\HR\ImportHrDataMigrationAction;
use App\Actions\HR\PrepareHrDataMigrationAction;
use App\Actions\HR\RollbackHrDataMigrationAction;
use App\Actions\HR\ValidateHrDataMigrationAction;
use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\GeneratePayrollEntriesAction;
use App\Actions\Payroll\PostPayrollRunAction;
use App\Actions\Payroll\SubmitPayrollRunAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\AttendanceSummaryStatus;
use App\Enums\CompensationStatus;
use App\Enums\EmploymentCategory;
use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use App\Enums\PayrollAccountComponent;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Filament\Pages\HrOperationalReadiness;
use App\Filament\Resources\HrDataMigrations\Pages\ListHrDataMigrations;
use App\Filament\Resources\HrDataMigrations\Pages\ViewHrDataMigration;
use App\Models\AttendanceMonthlySummary;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\Department;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\FixedAsset;
use App\Models\HrDataMigration;
use App\Models\LeaveType;
use App\Models\PayrollAccountMapping;
use App\Models\PayrollCalculationRule;
use App\Models\PayrollRun;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Reports\HrOperationalReadinessReport;
use App\Reports\HrRecoveryManifest;
use App\Reports\PayrollReconciliationReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HrMigrationAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_department_dry_run_requires_three_actors_reconciles_and_rolls_back(): void
    {
        [$company, $preparer, $validator, $importer] = $this->foundation();
        $csv = "code,name,parent_code,description,is_active\n"
            ."FIN,Finance,,Finance root,1\n"
            ."FIN-OPS,Finance Operations,FIN,Operations,1\n";

        $migration = app(PrepareHrDataMigrationAction::class)->handle(
            $company,
            HrDataMigrationType::Departments,
            $preparer,
            'approved-departments.csv',
            $csv,
        );
        $this->assertSame(2, $migration->valid_row_count);
        Storage::disk('local')->assertExists($migration->source_path);
        $this->assertSame(
            $migration->source_checksum,
            hash('sha256', Storage::disk('local')->get($migration->source_path)),
        );
        $this->assertSame($migration->getKey(), app(PrepareHrDataMigrationAction::class)->handle(
            $company,
            HrDataMigrationType::Departments,
            $preparer,
            'same-source.csv',
            $csv,
        )->getKey());

        app(ValidateHrDataMigrationAction::class)->handle($migration, $validator);
        $this->expectValidationException(
            fn () => app(ImportHrDataMigrationAction::class)->handle($migration, $validator),
            'actor',
        );
        $imported = app(ImportHrDataMigrationAction::class)->handle($migration, $importer);

        $this->assertSame(HrDataMigrationStatus::Imported, $imported->status);
        $this->assertSame($imported->source_totals, $imported->imported_totals);
        $this->assertSame(
            Department::query()->where('code', 'FIN')->value('id'),
            Department::query()->where('code', 'FIN-OPS')->value('parent_department_id'),
        );

        $rolledBack = app(RollbackHrDataMigrationAction::class)->handle(
            $imported,
            $importer,
            'Approved pilot source withdrawal',
        );
        $this->assertSame(HrDataMigrationStatus::RolledBack, $rolledBack->status);
        $this->assertSame(0, Department::query()->whereBelongsTo($company)->count());
        $this->assertSame(2, $rolledBack->rows()->count(), 'Immutable row evidence is retained.');
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => $rolledBack::class,
            'subject_id' => $rolledBack->getKey(),
            'event' => 'rolled-back',
        ]);

        $this->actingAs($importer);
        Filament::setTenant($company);
        Livewire::test(ListHrDataMigrations::class)->assertSuccessful();
        Livewire::test(ViewHrDataMigration::class, ['record' => $rolledBack->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_employee_import_rejects_duplicates_and_cross_company_references(): void
    {
        [$company, $preparer, $validator, $importer] = $this->foundation();
        $other = Company::factory()->create();
        Department::factory()->create(['company_id' => $other, 'code' => 'OTHER']);
        $invalid = $this->employeeHeaders()
            ."EMP-00001,Aaraif,2026-07-01,,permanent,active,OTHER,,,,,,,\n";
        $dryRun = app(PrepareHrDataMigrationAction::class)->handle(
            $company,
            HrDataMigrationType::Employees,
            $preparer,
            'invalid.csv',
            $invalid,
        );
        $this->assertSame(0, $dryRun->valid_row_count);
        $this->assertNotEmpty($dryRun->rows->first()->validation_errors);

        $csv = $this->employeeHeaders()
            ."EMP-00001,Aaraif,2026-07-01,,permanent,active,,,,,,,,\n"
            ."EMP-00002,Manager,2026-07-01,,contract,active,,,EMP-00001,,,,,\n";
        $migration = $this->prepareValidateImport(
            $company,
            HrDataMigrationType::Employees,
            $csv,
            $preparer,
            $validator,
            $importer,
        );

        $this->assertSame(2, Employment::query()->whereBelongsTo($company)->count());
        $this->assertSame(
            Employment::query()->where('employee_code', 'EMP-00001')->value('id'),
            Employment::query()->where('employee_code', 'EMP-00002')->value('reporting_to_employment_id'),
        );
        $this->assertSame(2, $migration->imported_row_count);

        $duplicate = app(PrepareHrDataMigrationAction::class)->handle(
            $company,
            HrDataMigrationType::Employees,
            $preparer,
            'duplicate.csv',
            $this->employeeHeaders()
                ."EMP-00001,Duplicate,2026-07-01,,permanent,active,,,,,,,,\n",
        );
        $this->assertSame(0, $duplicate->valid_row_count);
    }

    public function test_all_remaining_approved_source_adapters_import_and_reconcile(): void
    {
        [$company, $preparer, $validator, $importer] = $this->foundation();
        app(ProvisionDefaultDocumentCategoriesAction::class)->handle($company);
        app(ProvisionDefaultHrDocumentTypesAction::class)->handle($company);
        $employment = Employment::factory()->forCompany($company)->create(['employee_code' => 'EMP-00010']);
        $leaveType = LeaveType::factory()->forCompany($company)->create(['code' => 'ANNUAL']);
        $asset = FixedAsset::factory()->create([
            'company_id' => $company,
            'asset_number' => 'FA-00010',
        ]);

        $sources = [
            [HrDataMigrationType::DocumentMetadata, "employee_code,scope,document_type_code,title,reference_number,issue_date,expiry_date,description\n"
                ."EMP-00010,employee,cnic,CNIC metadata,CNIC-10,2026-01-01,,Approved metadata only\n",
            ],
            [HrDataMigrationType::LeaveBalances, "employee_code,leave_type_code,as_of_date,opening_units,source_reference\n"
                ."EMP-00010,ANNUAL,2026-07-01,12.50,OPEN-LV-10\n",
            ],
            [HrDataMigrationType::Financings, "employee_code,type,request_date,principal,finance_charge,installment_count,first_due_date,approved_source_reference\n"
                ."EMP-00010,advance,2026-06-01,20000,0,2,2026-07-31,ADV-OPEN-10\n",
            ],
            [HrDataMigrationType::AssetCustody, "employee_code,asset_number,issued_on,issued_condition,issued_location,source_reference\n"
                ."EMP-00010,FA-00010,2026-06-01,Good,Head Office,ASSET-OPEN-10\n",
            ],
            [HrDataMigrationType::HistoricalAttendance, "employee_code,period_start,period_end,scheduled_days,present_days,absent_days,half_days,leave_days,late_minutes,overtime_minutes,unpaid_leave_units,source_reference\n"
                ."EMP-00010,2026-06-01,2026-06-30,26,24,1,1,0,45,120,1.00,ATT-OPEN-10\n",
            ],
        ];
        foreach ($sources as [$type, $csv]) {
            $migration = $this->prepareValidateImport(
                $company,
                $type,
                $csv,
                $preparer,
                $validator,
                $importer,
            );
            $this->assertSame($migration->source_totals, $migration->imported_totals);
        }

        $this->assertSame('12.50', $employment->leaveLedgerEntries()->value('units'));
        $this->assertSame('20000.0000', $employment->employeeFinancings()->first()->outstandingAmount());
        $this->assertSame(2, $employment->employeeFinancings()->first()->installments()->count());
        $this->assertSame($employment->getKey(), $asset->refresh()->custodian_employment_id);
        $this->assertSame(1, $employment->attendanceMonthlySummaries()->count());
        $this->assertSame(1, $employment->employee->documents()->count());
    }

    public function test_recovery_readiness_security_and_realistic_volume_queries_are_bounded(): void
    {
        [$company, $actor] = $this->foundation();
        Employment::factory()->count(250)->forCompany($company)->create();
        $manifest = app(HrRecoveryManifest::class)->generate($company, $actor);
        $this->assertTrue(app(HrRecoveryManifest::class)->verify($company, $actor, $manifest)['matches']);
        Employment::query()->whereBelongsTo($company)->firstOrFail()->update(['notice_period_days' => 30]);
        $this->assertFalse(app(HrRecoveryManifest::class)->verify($company, $actor, $manifest)['matches']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $readiness = app(HrOperationalReadinessReport::class)->forCompany($company, $actor);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertTrue($readiness['integrity']['passes']);
        $this->assertTrue($readiness['reconciliation']['payroll_gl_passes']);
        $this->assertTrue($readiness['device_offline_continuity']['normalized_csv_fallback_available']);
        $this->assertFalse($readiness['device_offline_continuity']['device_specific_connector_verified']);
        $this->assertLessThanOrEqual(35, $queryCount);

        $this->actingAs($actor);
        Filament::setTenant($company);
        Livewire::test(HrOperationalReadiness::class)->assertSuccessful();

        $unauthorized = User::factory()->create();
        $this->expectValidationException(
            fn () => app(HrRecoveryManifest::class)->generate($company, $unauthorized),
            'authorization',
        );
    }

    public function test_pilot_attendance_reconciles_through_payroll_gl_and_treasury(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'employment_category' => EmploymentCategory::AdministrativeStaff,
        ]);
        EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'status' => CompensationStatus::Approved,
            'effective_from' => '2026-01-01',
            'basic_salary' => 100000,
            'house_travel_allowance' => 0,
            'food_allowance' => 0,
            'other_allowance' => 0,
            'approved_by_id' => $approver->getKey(),
            'approved_at' => now(),
        ]);
        PayrollCalculationRule::factory()->forCompany($company)->create();
        $summary = AttendanceMonthlySummary::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'status' => AttendanceSummaryStatus::Finalized,
            'scheduled_days' => 31,
            'present_days' => 30,
            'absent_days' => 1,
            'source_checksum' => hash('sha256', 'hr12-pilot-attendance'),
            'finalized_by_id' => $approver->getKey(),
            'finalized_at' => now(),
        ]);
        $run = PayrollRun::factory()->create([
            'company_id' => $company->getKey(),
            'created_by_id' => $maker->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        $salaryAccountId = $company->accounts()
            ->where('account_type', 'expense')
            ->where('allows_manual_posting', true)
            ->where('is_active', true)
            ->value('id');
        foreach ([PayrollAccountComponent::BasicSalary, PayrollAccountComponent::AbsenceDeduction] as $component) {
            PayrollAccountMapping::create([
                'company_id' => $company->getKey(),
                'component' => $component,
                'account_id' => $salaryAccountId,
                'is_active' => true,
            ]);
        }

        app(GeneratePayrollEntriesAction::class)->handle($run, $maker);
        $entry = $run->entries()->sole();
        $this->assertSame($summary->getKey(), $entry->components()
            ->where('type', 'absence_deduction')->sole()->source_id);
        app(SubmitPayrollRunAction::class)->handle($run, $maker);
        app(ApprovePayrollRunAction::class)->handle($run->fresh(), $approver);
        app(PostPayrollRunAction::class)->handle($run->fresh(), $poster);

        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $payment = TreasuryTransaction::factory()->paymentFrom(
            $company,
            $bank->accountingMapping()->firstOrFail()->account,
            $bank,
        )->create([
            'purpose' => TreasuryPurpose::Settlement,
            'counterparty_type' => TreasuryCounterpartyType::Employment,
            'employment_id' => $employment->getKey(),
            'party_id' => null,
            'amount' => (string) $entry->net_salary,
            'transaction_date' => '2026-07-31',
            'prepared_by_id' => $maker->getKey(),
        ]);
        $payment->allocations()->create([
            'company_id' => $company->getKey(),
            'allocatable_type' => $entry::class,
            'allocatable_id' => $entry->getKey(),
            'allocation_type' => TreasuryAllocationType::PayrollEntry,
            'amount' => (string) $entry->net_salary,
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($payment, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($payment->fresh(), $approver);
        app(PostTreasuryTransactionAction::class)->handle($payment->fresh(), $poster);

        $reconciliation = app(PayrollReconciliationReport::class)->forCompany($company)->sole();
        $this->assertTrue($reconciliation['reconciled']);
        $this->assertSame('0.0000', $reconciliation['open']);
        $this->assertSame(0, bccomp((string) $entry->net_salary, $reconciliation['settled'], 4));
    }

    /**
     * @return array{Company, User, User, User}
     */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, ...$users->all()];
    }

    private function prepareValidateImport(
        Company $company,
        HrDataMigrationType $type,
        string $csv,
        User $preparer,
        User $validator,
        User $importer,
    ): HrDataMigration {
        $migration = app(PrepareHrDataMigrationAction::class)->handle(
            $company,
            $type,
            $preparer,
            "{$type->value}.csv",
            $csv,
        );
        $this->assertSame($migration->row_count, $migration->valid_row_count);
        app(ValidateHrDataMigrationAction::class)->handle($migration, $validator);

        return app(ImportHrDataMigrationAction::class)->handle($migration, $importer);
    }

    private function employeeHeaders(): string
    {
        return "employee_code,full_name,joining_date,ending_date,employment_type,employment_status,department_code,designation_code,reporting_manager_employee_code,work_location_code,probation_start,probation_end,confirmation_date,notice_period_days\n";
    }

    private function expectValidationException(callable $callback, string $key): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }
}
