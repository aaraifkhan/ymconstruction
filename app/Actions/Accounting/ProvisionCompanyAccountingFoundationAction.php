<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingProfile;
use App\Enums\FinancialPeriodStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\AccountingSetting;
use App\Models\AccountTemplate;
use App\Models\ApMatchingSetting;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\FinancialYear;
use App\Models\VoucherSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProvisionCompanyAccountingFoundationAction
{
    public function __construct(
        private PreviewCompanyAccountProvisioningAction $preview,
        private SyncCompanyBankAccountGlAction $syncBankAccount,
    ) {}

    /** @return array<string, int> */
    public function handle(Company $company, AccountingProfile $profile, ?CarbonImmutable $asOf = null): array
    {
        return DB::transaction(function () use ($company, $profile, $asOf): array {
            $preview = $this->preview->handle($company, $profile);
            if ($preview['conflicts'] !== []) {
                throw ValidationException::withMessages(['accounts' => $preview['conflicts']]);
            }

            $settings = AccountingSetting::firstOrCreate(['company_id' => $company->getKey()], [
                'profile' => $profile, 'base_currency_code' => 'PKR', 'timezone' => 'Asia/Karachi',
                'fiscal_year_start_month' => 7, 'fiscal_year_start_day' => 1,
                'monetary_precision' => 4, 'display_precision' => 2,
                'inventory_valuation_method' => 'moving_weighted_average', 'allow_negative_inventory' => false,
            ]);
            ApMatchingSetting::firstOrCreate(
                ['company_id' => $company->getKey()],
                [
                    'quantity_tolerance_percentage' => 0,
                    'rate_tolerance_percentage' => 0,
                    'tax_tolerance_percentage' => 0,
                    'is_active' => true,
                ],
            );

            $accountsByTemplate = [];
            foreach (AccountTemplate::query()->orderBy('sort_order')->get() as $template) {
                $parentId = $template->parent_id ? $accountsByTemplate[$template->parent_id]->getKey() : null;
                $accountsByTemplate[$template->getKey()] = Account::firstOrCreate(
                    ['company_id' => $company->getKey(), 'code' => $template->code],
                    [
                        'parent_id' => $parentId, 'account_template_id' => $template->getKey(), 'name' => $template->name,
                        'account_type' => $template->account_type, 'reporting_group' => $template->reporting_group,
                        'normal_balance' => $template->normal_balance, 'system_key' => $template->system_key,
                        'is_control_account' => $template->is_control_account, 'allows_manual_posting' => $template->allows_manual_posting,
                        'is_system_generated' => true, 'is_active' => $template->isEnabledFor($settings->profile),
                        'sort_order' => $template->sort_order,
                    ],
                );
            }

            foreach ($accountsByTemplate as $account) {
                if ($account->system_key !== null) {
                    AccountingMapping::firstOrCreate(
                        ['company_id' => $company->getKey(), 'system_key' => $account->system_key],
                        ['account_id' => $account->getKey(), 'is_active' => true],
                    );
                }
            }

            $date = $asOf ?? CarbonImmutable::now($settings->timezone);
            $startYear = $date->month >= $settings->fiscal_year_start_month ? $date->year : $date->year - 1;
            $start = CarbonImmutable::create($startYear, $settings->fiscal_year_start_month, 1);
            $end = $start->addYear()->subDay();
            $year = FinancialYear::firstOrCreate(
                ['company_id' => $company->getKey(), 'name' => "FY {$start->year}-{$end->year}"],
                ['starts_on' => $start, 'ends_on' => $end, 'status' => FinancialPeriodStatus::Open],
            );

            foreach (range(0, 11) as $index) {
                $periodStart = $start->addMonths($index);
                FinancialPeriod::firstOrCreate(
                    ['financial_year_id' => $year->getKey(), 'period_number' => $index + 1],
                    [
                        'company_id' => $company->getKey(), 'name' => $periodStart->format('F Y'),
                        'starts_on' => $periodStart, 'ends_on' => $periodStart->endOfMonth(),
                        'status' => FinancialPeriodStatus::Open,
                    ],
                );
            }

            foreach (VoucherType::cases() as $type) {
                VoucherSequence::firstOrCreate(
                    ['company_id' => $company->getKey(), 'financial_year_id' => $year->getKey(), 'voucher_type' => $type],
                    ['prefix' => $type->prefix(), 'next_number' => 1, 'padding' => 6, 'is_active' => true],
                );
            }

            foreach ($company->bankAccounts()->withTrashed()->get() as $bankAccount) {
                $this->syncBankAccount->handle($bankAccount);
            }

            activity('accounting_provisioning')->performedOn($company)->withProperties([
                'profile' => $profile->value, 'preview' => $preview, 'financial_year_id' => $year->getKey(),
            ])->log('Company accounting foundation provisioned');

            return ['accounts' => count($accountsByTemplate), 'periods' => 12, 'voucher_sequences' => count(VoucherType::cases())];
        });
    }
}
