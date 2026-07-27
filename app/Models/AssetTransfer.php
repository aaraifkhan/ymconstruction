<?php

namespace App\Models;

use Database\Factories\AssetTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id', 'fixed_asset_id', 'from_custodian_employment_id', 'to_custodian_employment_id',
    'from_project_id', 'to_project_id', 'from_project_site_id', 'to_project_site_id',
    'from_cost_center_id', 'to_cost_center_id', 'from_location', 'to_location',
    'effective_on', 'reason', 'transferred_by_id',
])]
class AssetTransfer extends Model
{
    /** @use HasFactory<AssetTransferFactory> */
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    protected function casts(): array
    {
        return ['effective_on' => 'date'];
    }
}
