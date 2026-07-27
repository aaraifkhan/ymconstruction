<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\AccountTemplate;
use Illuminate\Support\Facades\DB;

class ProvisionStandardAccountTemplatesAction
{
    public function handle(): int
    {
        return DB::transaction(function (): int {
            $templatesByCode = [];

            foreach ($this->definitions() as $position => $definition) {
                $parentCode = $definition['parent_code'];
                $template = AccountTemplate::withTrashed()->updateOrCreate(
                    ['code' => $definition['code']],
                    [
                        'parent_id' => $parentCode ? $templatesByCode[$parentCode]->getKey() : null,
                        'name' => $definition['name'],
                        'account_type' => $definition['type'],
                        'reporting_group' => $definition['group'],
                        'normal_balance' => in_array($definition['type'], [AccountType::Asset, AccountType::Expense], true) ? NormalBalance::Debit : NormalBalance::Credit,
                        'system_key' => $definition['key'] ?? null,
                        'activation_profiles' => isset($definition['profiles']) ? array_map(fn (AccountingProfile $profile) => $profile->value, $definition['profiles']) : null,
                        'is_control_account' => $definition['control'] ?? false,
                        'allows_manual_posting' => $definition['manual'] ?? false,
                        'is_active' => true,
                        'sort_order' => $position + 1,
                        'deleted_at' => null,
                    ],
                );
                $templatesByCode[$template->code] = $template;
            }

            return count($templatesByCode);
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        $a = AccountType::Asset;
        $l = AccountType::Liability;
        $e = AccountType::Equity;
        $r = AccountType::Revenue;
        $x = AccountType::Expense;
        $c = AccountingProfile::Construction;
        $it = AccountingProfile::ItServices;
        $mb = AccountingProfile::MedicalBilling;
        $t = AccountingProfile::Trading;
        $row = fn (string $code, string $name, AccountType $type, ?string $parent = null, ?AccountingMappingKey $key = null, bool $control = false, bool $manual = false, ?array $profiles = null, ?string $group = null): array => [
            'code' => $code, 'name' => $name, 'type' => $type, 'parent_code' => $parent, 'key' => $key,
            'control' => $control, 'manual' => $manual, 'profiles' => $profiles, 'group' => $group ?? $type->value,
        ];

        return [
            $row('1000', 'Assets', $a), $row('1100', 'Current Assets', $a, '1000'),
            $row('1110', 'Cash in Hand', $a, '1100'), $row('1111', 'Head Office Cash', $a, '1110', AccountingMappingKey::DefaultCash, false, true),
            $row('1112', 'Site Petty Cash', $a, '1110', null, false, true, [$c]),
            $row('1113', 'Director Cash / Advance', $a, '1110', null, false, true),
            $row('1120', 'Bank Accounts', $a, '1100', AccountingMappingKey::BankAccounts),
            $row('1130', 'Accounts Receivable', $a, '1100', AccountingMappingKey::AccountsReceivable, true),
            $row('1140', 'Employee Advances', $a, '1100', AccountingMappingKey::EmployeeAdvances, true),
            $row('1150', 'Vendor Advances', $a, '1100', AccountingMappingKey::VendorAdvances, true),
            $row('1160', 'Security Deposits Paid', $a, '1100', null, false, true),
            $row('1170', 'Other Receivables', $a, '1100', null, false, true),
            $row('1180', 'Retention Receivable', $a, '1100', AccountingMappingKey::RetentionReceivable, true, false, [$c]),
            $row('1185', 'WHT Receivable', $a, '1100', AccountingMappingKey::WhtReceivable, true),
            $row('1190', 'Input Tax', $a, '1100', AccountingMappingKey::InputTax, true),
            $row('1195', 'Site Inventory', $a, '1100', AccountingMappingKey::SiteInventory, true, false, [$c, $t]),
            $row('1196', 'Work in Progress', $a, '1100', AccountingMappingKey::WorkInProgress, true, false, [$c]),
            $row('1197', 'Due from Related Companies', $a, '1100', AccountingMappingKey::DueFromRelatedCompanies, true),
            $row('1200', 'Fixed Assets', $a, '1000'),
            ...array_map(fn (array $v) => $row($v[0], $v[1], $a, '1200', null, false, true), [
                ['1210', 'Land'], ['1220', 'Building'], ['1230', 'Furniture'], ['1240', 'Computers'], ['1250', 'Servers'], ['1260', 'Machinery'], ['1270', 'Vehicles'], ['1280', 'Office Equipment'], ['1290', 'Accumulated Depreciation'],
            ]),
            $row('2000', 'Liabilities', $l), $row('2100', 'Current Liabilities', $l, '2000'),
            $row('2110', 'Accounts Payable', $l, '2100', AccountingMappingKey::AccountsPayable, true),
            $row('2120', 'Contractor Payable', $l, '2100', null, true, false, [$c]),
            $row('2130', 'Supplier Payable', $l, '2100', null, true),
            $row('2140', 'Salary Payable', $l, '2100', AccountingMappingKey::SalaryPayable, true),
            $row('2150', 'WHT Payable', $l, '2100', AccountingMappingKey::WhtPayable, true),
            $row('2160', 'Output Tax', $l, '2100', AccountingMappingKey::OutputTax, true),
            $row('2170', 'Utility Bills Payable', $l, '2100', null, false, true),
            $row('2180', 'Rent Payable', $l, '2100', null, false, true),
            $row('2190', 'Security Deposits Received', $l, '2100', null, false, true),
            $row('2191', 'Retention Payable', $l, '2100', AccountingMappingKey::RetentionPayable, true, false, [$c]),
            $row('2192', 'Customer / Mobilization Advances', $l, '2100', AccountingMappingKey::CustomerAdvances, true),
            $row('2193', 'Goods Received Not Invoiced', $l, '2100', AccountingMappingKey::Grni, true),
            $row('2195', 'Due to Related Companies', $l, '2100', AccountingMappingKey::DueToRelatedCompanies, true),
            $row('2200', 'Long Term Liabilities', $l, '2000'),
            ...array_map(fn (array $v) => $row($v[0], $v[1], $l, '2200', null, false, true), [['2210', 'Bank Loan'], ['2220', 'Director Loan'], ['2230', 'Partner Loan'], ['2240', 'Vehicle Loan']]),
            $row('3000', 'Equity', $e), $row('3100', 'Paid-up Capital', $e, '3000', null, false, true),
            $row('3200', 'Retained Earnings', $e, '3000', AccountingMappingKey::RetainedEarnings),
            $row('3300', 'Current Year Profit', $e, '3000', AccountingMappingKey::CurrentYearResult),
            $row('4000', 'Revenue', $r),
            $row('4100', 'Construction Revenue', $r, '4000', null, false, true, [$c]),
            $row('4200', 'IT Services', $r, '4000', null, false, true, [$it]),
            $row('4300', 'Medical Billing Income', $r, '4000', null, false, true, [$mb]),
            $row('4400', 'Trading Sales', $r, '4000', null, false, true, [$t]),
            $row('4500', 'Consultancy Income', $r, '4000', null, false, true),
            $row('4600', 'Rental Income', $r, '4000', null, false, true), $row('4700', 'Other Income', $r, '4000', null, false, true),
            $row('5000', 'Expenses', $x),
            ...array_map(fn (array $v) => $row($v[0], $v[1], $x, '5000', null, false, true), [
                ['5100', 'Salaries'], ['5200', 'Fuel'], ['5300', 'Office Rent'], ['5400', 'Utilities'], ['5500', 'Internet'], ['5600', 'Vehicle Rent'], ['5700', 'Printing'], ['5800', 'Stationery'], ['5900', 'Repairs & Maintenance'], ['6000', 'Vehicle Maintenance'], ['6100', 'Depreciation'], ['6200', 'Marketing'], ['6300', 'Legal & Professional'], ['6400', 'Audit'], ['6500', 'Consultancy'], ['6600', 'Software Subscription'], ['6700', 'Entertainment'], ['6800', 'Travelling & Conveyance'], ['6900', 'Miscellaneous'],
            ]),
            $row('7000', 'Project / Direct Costs', $x),
            ...array_map(fn (array $v) => $row($v[0], $v[1], $x, '7000', null, false, true, [$c]), [
                ['7100', 'Cement'], ['7110', 'Steel'], ['7120', 'Sand'], ['7130', 'Crush'], ['7140', 'Bricks'], ['7150', 'Electrical'], ['7160', 'Plumbing'], ['7170', 'Paint'], ['7180', 'Tiles'], ['7190', 'Labor'], ['7200', 'Machinery Rental'], ['7210', 'Excavation'], ['7220', 'Concrete Pump'], ['7230', 'Shuttering'], ['7240', 'Safety Equipment'], ['7250', 'Site Office'], ['7260', 'Site Utilities'], ['7270', 'Site Security'], ['7280', 'Project Transportation'],
            ]),
            $row('7300', 'Cost of Goods Sold', $x, '7000', null, false, true, [$t]),
        ];
    }
}
