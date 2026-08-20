<?php

namespace App\Actions\Assets;

use App\Enums\AssetCustodyStatus;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\EmployeeAssetCustody;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferGeneralAssetCustodyAction
{
    public function handle(
        FixedAsset $asset,
        ?int $targetCompanyId,
        ?int $targetEmploymentId = null,
        ?string $location = null,
        ?string $condition = 'Good',
        ?string $handoverNotes = null,
        ?User $actor = null,
    ): FixedAsset {
        return DB::transaction(function () use (
            $asset,
            $targetCompanyId,
            $targetEmploymentId,
            $location,
            $condition,
            $handoverNotes,
            $actor,
        ): FixedAsset {
            $actorUser = $actor ?? auth()->user() ?? User::query()->first();

            // 1. Close any existing open custody records for this asset
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
                ->each(function (EmployeeAssetCustody $custody) use ($actorUser, $condition, $handoverNotes): void {
                    $custody->status = EmployeeAssetCustodyStatus::Transferred;
                    $custody->returned_on = today()->toDateString();
                    $custody->return_condition = $condition ?? 'Good';
                    $custody->return_notes = "Transferred: {$handoverNotes}";
                    $custody->returned_by_id = $actorUser?->getKey();
                    $custody->returned_at = now();
                    $custody->save();
                });

            // 2. Determine new custody status
            $newCustodyStatus = AssetCustodyStatus::InPool;
            if ($targetEmploymentId !== null) {
                $newCustodyStatus = AssetCustodyStatus::AssignedToEmployee;
            } elseif ($targetCompanyId !== null) {
                $newCustodyStatus = AssetCustodyStatus::AssignedToCompany;
            }

            // 3. Update FixedAsset
            $asset->assigned_company_id = $targetCompanyId;
            $asset->custodian_employment_id = $targetEmploymentId;
            $asset->location = $location ?? $asset->location;
            $asset->condition_on_assignment = $condition;
            $asset->handover_notes = $handoverNotes;
            $asset->assigned_at = now();
            $asset->custody_status = $newCustodyStatus;
            $asset->save();

            // 4. If assigned to an employee, create new custody record
            if ($targetEmploymentId !== null) {
                $custodyCompanyId = $targetCompanyId ?? $asset->company_id;
                EmployeeAssetCustody::create([
                    'company_id' => $custodyCompanyId,
                    'fixed_asset_id' => $asset->getKey(),
                    'employment_id' => $targetEmploymentId,
                    'reference_number' => 'CUST-'.Str::upper(Str::random(6)),
                    'status' => EmployeeAssetCustodyStatus::Issued,
                    'issued_on' => today()->toDateString(),
                    'issued_condition' => $condition ?? 'Good',
                    'issued_location' => $location ?? $asset->location ?? 'Office',
                    'issue_notes' => $handoverNotes ?? "Transferred custody to employee ({$asset->name})",
                    'prepared_by_id' => $actorUser?->getKey(),
                    'issued_by_id' => $actorUser?->getKey(),
                    'issued_at' => now(),
                ]);
            }

            return $asset;
        });
    }
}
