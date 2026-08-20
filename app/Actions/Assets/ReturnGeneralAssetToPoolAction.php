<?php

namespace App\Actions\Assets;

use App\Enums\AssetCustodyStatus;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\EmployeeAssetCustody;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReturnGeneralAssetToPoolAction
{
    public function handle(
        FixedAsset $asset,
        ?string $returnCondition = 'Good',
        ?string $returnNotes = null,
        ?User $actor = null,
    ): FixedAsset {
        return DB::transaction(function () use (
            $asset,
            $returnCondition,
            $returnNotes,
            $actor,
        ): FixedAsset {
            $actorUser = $actor ?? auth()->user() ?? User::query()->first();

            // 1. Close any active custody records
            EmployeeAssetCustody::query()
                ->where('fixed_asset_id', $asset->getKey())
                ->whereIn('status', [
                    EmployeeAssetCustodyStatus::Draft,
                    EmployeeAssetCustodyStatus::Issued,
                    EmployeeAssetCustodyStatus::Acknowledged,
                    EmployeeAssetCustodyStatus::ReturnPending,
                    EmployeeAssetCustodyStatus::Exception,
                ])
                ->get()
                ->each(function (EmployeeAssetCustody $custody) use ($actorUser, $returnCondition, $returnNotes): void {
                    $custody->status = EmployeeAssetCustodyStatus::Returned;
                    $custody->returned_on = today()->toDateString();
                    $custody->return_condition = $returnCondition ?? 'Good';
                    $custody->return_notes = "Returned to Pool: {$returnNotes}";
                    $custody->returned_by_id = $actorUser?->getKey();
                    $custody->returned_at = now();
                    $custody->save();
                });

            // 2. Update FixedAsset
            $asset->assigned_company_id = null;
            $asset->custodian_employment_id = null;
            $asset->location = 'Head Office Storage / Central Pool';
            $asset->condition_on_assignment = $returnCondition;
            $asset->handover_notes = $returnNotes;
            $asset->custody_status = AssetCustodyStatus::InPool;
            $asset->save();

            return $asset;
        });
    }
}
