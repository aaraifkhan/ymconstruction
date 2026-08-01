<?php

namespace App\Actions\Companies;

use App\Enums\CompanyModuleState;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProvisionOrganizationCompaniesAction
{
    /**
     * @var array<int, array{name: string, slug: string, logo_path: string}>
     */
    private const COMPANY_DEFINITIONS = [
        ['name' => 'BMC Construction', 'slug' => 'bmc-construction', 'logo_path' => 'images/company-logos/bmc-construction.png'],
        ['name' => 'YMC Construction', 'slug' => 'ymc-construction', 'logo_path' => 'images/company-logos/ymc-construction.png'],
        ['name' => '7 Orbit', 'slug' => '7-orbit', 'logo_path' => 'images/company-logos/7-orbit.png'],
        ['name' => '7 Orbit Medical Billing', 'slug' => '7-orbit-medical-billing', 'logo_path' => 'images/company-logos/7-orbit-medical-billing.png'],
    ];

    /**
     * @var array<int, string>
     */
    private const MODULE_KEYS = [
        'documents',
        'hr',
        'accounts',
        'projects',
    ];

    /**
     * @return Collection<string, Company>
     */
    public function handle(): Collection
    {
        return DB::transaction(function (): Collection {
            $modules = Module::query()
                ->active()
                ->whereIn('key', self::MODULE_KEYS)
                ->get()
                ->keyBy('key');

            $missingModuleKeys = collect(self::MODULE_KEYS)->diff($modules->keys());

            if ($missingModuleKeys->isNotEmpty()) {
                throw new LogicException(
                    'Organization provisioning requires these active modules: '.$missingModuleKeys->implode(', ').'.',
                );
            }

            /** @var Collection<string, Company> $companies */
            $companies = collect();

            foreach (self::COMPANY_DEFINITIONS as $definition) {
                $company = $this->provisionCompany($definition);
                $companies->put($definition['slug'], $company);
            }

            foreach ($companies as $company) {
                foreach ($modules as $module) {
                    CompanyModule::query()->firstOrCreate(
                        [
                            'company_id' => $company->getKey(),
                            'module_id' => $module->getKey(),
                        ],
                        [
                            'state' => CompanyModuleState::Enabled,
                            'variant' => null,
                            'settings' => null,
                        ],
                    );
                }
            }

            return $companies;
        });
    }

    /**
     * @param  array{name: string, slug: string, logo_path: string}  $definition
     */
    private function provisionCompany(array $definition): Company
    {
        $companyBySlug = Company::withTrashed()
            ->where('slug', $definition['slug'])
            ->first();
        $companyByName = Company::withTrashed()
            ->where('name', $definition['name'])
            ->first();

        if ($companyBySlug !== null && $companyByName !== null && ! $companyBySlug->is($companyByName)) {
            throw new LogicException(
                "Company identity conflict for [{$definition['name']}] and slug [{$definition['slug']}].",
            );
        }

        $company = $companyBySlug ?? $companyByName ?? new Company;

        if ($company->trashed()) {
            $company->restore();
        }

        $company->fill([
            'parent_company_id' => null,
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'legal_name' => filled($company->legal_name) ? $company->legal_name : $definition['name'],
            'country_code' => filled($company->country_code) ? $company->country_code : 'PK',
            'currency_code' => filled($company->currency_code) ? $company->currency_code : 'PKR',
            'timezone' => filled($company->timezone) ? $company->timezone : 'Asia/Karachi',
            'logo_path' => $definition['logo_path'],
            'is_active' => true,
        ]);
        $company->save();

        return $company;
    }
}
