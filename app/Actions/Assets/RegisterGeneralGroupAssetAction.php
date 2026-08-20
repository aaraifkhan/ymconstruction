<?php

namespace App\Actions\Assets;

use App\Enums\AccountType;
use App\Enums\AssetAcquisitionSource;
use App\Enums\AssetCustodyStatus;
use App\Enums\AssetStatus;
use App\Enums\DepreciationMethod;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\Account;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\EmployeeAssetCustody;
use App\Models\FixedAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterGeneralGroupAssetAction
{
    public function handle(
        Company $ownerCompany,
        string $name,
        ?int $assetCategoryId = null,
        ?string $assetNumber = null,
        ?string $serialNumber = null,
        ?string $location = null,
        ?CarbonImmutable $acquiredOn = null,
        string|float $acquisitionCost = 0,
        ?int $assignedCompanyId = null,
        ?int $custodianEmploymentId = null,
        ?string $conditionOnAssignment = 'Good',
        ?string $handoverNotes = null,
        ?string $notes = null,
        ?User $actor = null,
    ): FixedAsset {
        return DB::transaction(function () use (
            $ownerCompany,
            $name,
            $assetCategoryId,
            $assetNumber,
            $serialNumber,
            $location,
            $acquiredOn,
            $acquisitionCost,
            $assignedCompanyId,
            $custodianEmploymentId,
            $conditionOnAssignment,
            $handoverNotes,
            $notes,
            $actor,
        ): FixedAsset {
            $acquiredDate = $acquiredOn ?? CarbonImmutable::now();
            $actorUser = $actor ?? auth()->user() ?? User::query()->first();

            // Ensure category
            $category = $this->resolveCategory($ownerCompany, $assetCategoryId);

            // Auto-generate Asset Number if not provided
            if (empty($assetNumber)) {
                $count = FixedAsset::withoutGlobalScopes()->where('company_id', $ownerCompany->getKey())->count() + 1;
                $assetNumber = 'GRP-AST-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
                while (FixedAsset::withoutGlobalScopes()->where('company_id', $ownerCompany->getKey())->where('asset_number', $assetNumber)->exists()) {
                    $count++;
                    $assetNumber = 'GRP-AST-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
                }
            }

            // Determine custody status
            $custodyStatus = AssetCustodyStatus::InPool;
            if ($custodianEmploymentId !== null) {
                $custodyStatus = AssetCustodyStatus::AssignedToEmployee;
            } elseif ($assignedCompanyId !== null) {
                $custodyStatus = AssetCustodyStatus::AssignedToCompany;
            }

            $asset = new FixedAsset;
            $asset->company_id = $ownerCompany->getKey();
            $asset->assigned_company_id = $assignedCompanyId;
            $asset->asset_category_id = $category->getKey();
            $asset->custodian_employment_id = $custodianEmploymentId;
            $asset->asset_number = $assetNumber;
            $asset->name = $name;
            $asset->serial_number = $serialNumber;
            $asset->location = $location ?? ($assignedCompanyId ? 'Site / Branch Office' : 'Central Storage / Pool');
            $asset->acquisition_source = AssetAcquisitionSource::Manual;
            $asset->acquired_on = $acquiredDate;
            $asset->available_for_use_on = $acquiredDate;
            $asset->acquisition_cost = max(0.01, (float) $acquisitionCost);
            $asset->residual_value = 0;
            $asset->useful_life_months = $category->default_useful_life_months ?? 36;
            $asset->depreciation_method = DepreciationMethod::StraightLine;
            $asset->accumulated_depreciation = 0;
            $asset->status = AssetStatus::Approved;
            $asset->custody_status = $custodyStatus;
            $asset->assigned_at = $custodianEmploymentId || $assignedCompanyId ? now() : null;
            $asset->condition_on_assignment = $conditionOnAssignment;
            $asset->handover_notes = $handoverNotes;
            $asset->notes = $notes;
            $asset->prepared_by_id = $actorUser->getKey();
            $asset->approved_by_id = $actorUser->getKey();
            $asset->approved_at = now();
            $asset->save();

            // If assigned to an employee, create custody log record
            if ($custodianEmploymentId !== null) {
                $custodyCompanyId = $assignedCompanyId ?? $ownerCompany->getKey();
                EmployeeAssetCustody::create([
                    'company_id' => $custodyCompanyId,
                    'fixed_asset_id' => $asset->getKey(),
                    'employment_id' => $custodianEmploymentId,
                    'reference_number' => 'CUST-'.Str::upper(Str::random(6)),
                    'status' => EmployeeAssetCustodyStatus::Issued,
                    'issued_on' => $acquiredDate->toDateString(),
                    'issued_condition' => $conditionOnAssignment ?? 'Good',
                    'issued_location' => $location ?? 'Office',
                    'issue_notes' => $handoverNotes ?? "Issued during general asset registration ({$asset->name})",
                    'prepared_by_id' => $actorUser->getKey(),
                    'issued_by_id' => $actorUser->getKey(),
                    'issued_at' => now(),
                ]);
            }

            return $asset;
        });
    }

    private function resolveCategory(Company $company, ?int $categoryId): AssetCategory
    {
        if ($categoryId !== null) {
            $cat = AssetCategory::query()->where('company_id', $company->getKey())->where('is_active', true)->find($categoryId);
            if ($cat) {
                return $cat;
            }
        }

        $existing = AssetCategory::query()->where('company_id', $company->getKey())->where('is_active', true)->first();
        if ($existing) {
            return $existing;
        }

        // Auto-provision a default Category
        $costAccount = Account::withoutGlobalScopes()
            ->where('company_id', $company->getKey())
            ->where('account_type', AccountType::Asset)
            ->where(function ($q) {
                $q->where('code', 'LIKE', '12%')
                    ->orWhere('name', 'LIKE', '%Asset%')
                    ->orWhere('name', 'LIKE', '%Equipment%');
            })
            ->where('allows_manual_posting', true)
            ->first()
            ?? Account::withoutGlobalScopes()
                ->where('company_id', $company->getKey())
                ->where('account_type', AccountType::Asset)
                ->where('allows_manual_posting', true)
                ->first();

        if (! $costAccount) {
            $costAccount = Account::create([
                'company_id' => $company->getKey(),
                'code' => '1280',
                'name' => 'General & Shared Fixed Assets',
                'account_type' => AccountType::Asset,
                'reporting_group' => AccountType::Asset->value,
                'allows_manual_posting' => true,
                'is_active' => true,
            ]);
        }

        return AssetCategory::create([
            'company_id' => $company->getKey(),
            'code' => 'GRP-GEN',
            'name' => 'Group Shared Assets & Equipment',
            'cost_account_id' => $costAccount->getKey(),
            'default_useful_life_months' => 36,
            'is_depreciable' => false,
            'is_active' => true,
        ]);
    }
}
