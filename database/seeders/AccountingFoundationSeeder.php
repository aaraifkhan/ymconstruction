<?php

namespace Database\Seeders;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AccountingFoundationSeeder extends Seeder
{
    public function run(ProvisionStandardAccountTemplatesAction $templates, ProvisionCompanyAccountingFoundationAction $companies): void
    {
        $templates->handle();
        $profiles = [
            'ym-construction' => AccountingProfile::Construction,
            '7-orbit-it' => AccountingProfile::ItServices,
            '7-orbit-medical-billing' => AccountingProfile::MedicalBilling,
            'bmc-trading' => AccountingProfile::Trading,
        ];
        Company::query()->each(fn (Company $company) => $companies->handle($company, $profiles[$company->slug] ?? AccountingProfile::Generic));
    }
}
