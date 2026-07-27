<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\FixedAsset;
use Illuminate\Support\Collection;

class FixedAssetRegisterReport
{
    /** @return Collection<int, FixedAsset> */
    public function forCompany(Company $company): Collection
    {
        return FixedAsset::query()
            ->whereBelongsTo($company)
            ->with(['category', 'project', 'projectSite', 'costCenter', 'depreciationLines.depreciationRun'])
            ->orderBy('asset_number')
            ->get();
    }
}
