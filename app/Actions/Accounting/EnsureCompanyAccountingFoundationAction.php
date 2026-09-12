<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingProfile;
use App\Models\AccountingSetting;
use App\Models\Company;
use Carbon\CarbonImmutable;

class EnsureCompanyAccountingFoundationAction
{
    public function __construct(
        private ProvisionStandardAccountTemplatesAction $provisionTemplates,
        private ProvisionCompanyAccountingFoundationAction $provisionCompany,
    ) {}

    /**
     * Idempotently ensure templates, company COA, system mappings, and open periods exist.
     *
     * @return array<string, int>
     */
    public function handle(Company $company, ?CarbonImmutable $asOf = null): array
    {
        $this->provisionTemplates->handle();

        $profile = AccountingSetting::query()
            ->where('company_id', $company->getKey())
            ->value('profile');

        if ($profile instanceof AccountingProfile) {
            $resolvedProfile = $profile;
        } elseif (is_string($profile) && $profile !== '') {
            $resolvedProfile = AccountingProfile::from($profile);
        } else {
            $resolvedProfile = match ($company->slug) {
                'bmc-construction', 'ymc-construction' => AccountingProfile::Construction,
                '7-orbit' => AccountingProfile::ItServices,
                '7-orbit-medical-billing' => AccountingProfile::MedicalBilling,
                default => AccountingProfile::Generic,
            };
        }

        return $this->provisionCompany->handle($company, $resolvedProfile, $asOf);
    }
}
