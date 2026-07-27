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
     * @var array<int, array{name: string, slug: string, parent_slug: string|null}>
     */
    private const COMPANY_DEFINITIONS = [
        ['name' => '7-Orbit', 'slug' => '7-orbit', 'parent_slug' => null],
        ['name' => 'YM Construction', 'slug' => 'ym-construction', 'parent_slug' => null],
        ['name' => 'BMC', 'slug' => 'bmc', 'parent_slug' => null],
        ['name' => 'BMC Trading', 'slug' => 'bmc-trading', 'parent_slug' => null],
        ['name' => '7-Orbit IT', 'slug' => '7-orbit-it', 'parent_slug' => '7-orbit'],
        [
            'name' => '7-Orbit Medical Billing',
            'slug' => '7-orbit-medical-billing',
            'parent_slug' => '7-orbit',
        ],
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
                $parentCompany = $definition['parent_slug'] === null
                    ? null
                    : $companies->get($definition['parent_slug']);

                if ($definition['parent_slug'] !== null && $parentCompany === null) {
                    throw new LogicException("Parent company [{$definition['parent_slug']}] must be provisioned first.");
                }

                $company = $this->provisionCompany($definition, $parentCompany);
                $companies->put($definition['slug'], $company);
            }

            foreach ($companies as $company) {
                $defaultState = $company->parent_company_id === null
                    ? CompanyModuleState::Enabled
                    : CompanyModuleState::Inherit;

                foreach ($modules as $module) {
                    CompanyModule::query()->firstOrCreate(
                        [
                            'company_id' => $company->getKey(),
                            'module_id' => $module->getKey(),
                        ],
                        [
                            'state' => $defaultState,
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
     * @param  array{name: string, slug: string, parent_slug: string|null}  $definition
     */
    private function provisionCompany(array $definition, ?Company $parentCompany): Company
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
            'parent_company_id' => $parentCompany?->getKey(),
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'legal_name' => filled($company->legal_name) ? $company->legal_name : $definition['name'],
            'country_code' => filled($company->country_code) ? $company->country_code : 'PK',
            'currency_code' => filled($company->currency_code) ? $company->currency_code : 'PKR',
            'timezone' => filled($company->timezone) ? $company->timezone : 'Asia/Karachi',
            'is_active' => true,
        ]);
        $company->save();

        return $company;
    }
}
